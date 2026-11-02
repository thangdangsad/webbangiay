<?php

namespace App\Modules\Auth\Services;

use App\Support\ApiException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

/**
 * JWT access token + refresh token opaque (chỉ lưu hash SHA-256 trong Redis, xoay vòng mỗi lần dùng).
 * Redis là nguồn sự thật của phiên → thu hồi tức thời. Xem docs/docs/02-BE-design.md §6.
 */
class TokenService
{
    private array $cfg;

    public function __construct(public readonly string $aud)
    {
        $this->cfg = config("shoe.auth.{$aud}");
    }

    public static function for(string $aud): self
    {
        return new self($aud);
    }

    public function cfg(string $key): mixed
    {
        return $this->cfg[$key] ?? null;
    }

    private function redis()
    {
        return Redis::connection('auth');
    }

    private function k(string ...$parts): string
    {
        return "auth:{$this->aud}:".implode(':', $parts);
    }

    /** Tạo phiên mới. @return array{access_token:string,refresh_token:string,expires_in:int,sid:string,refresh_ttl:int} */
    public function issue(int $userId, array $device = []): array
    {
        $now = time();
        $sid = (string) Str::ulid();
        $refresh = bin2hex(random_bytes(32));
        $sha = hash('sha256', $refresh);
        $absolute = $now + $this->cfg['refresh_absolute'];
        $ttl = min($this->cfg['refresh_ttl'], $absolute - $now);

        $this->enforceMaxSessions($userId);

        $r = $this->redis();
        $r->pipeline(function ($p) use ($sid, $userId, $device, $sha, $now, $absolute, $ttl) {
            $p->hmset($this->k('sess', $sid), [
                'user_id' => $userId, 'device' => $device['device'] ?? '', 'ip' => $device['ip'] ?? '',
                'user_agent' => $device['user_agent'] ?? '', 'created_at' => $now, 'last_used_at' => $now,
                'refresh_hash' => $sha, 'prev_refresh_hash' => '', 'rotated_at' => 0, 'absolute_exp' => $absolute,
            ]);
            $p->expire($this->k('sess', $sid), $ttl);
            $p->setex($this->k('rt', $sha), $ttl, $sid);
            $p->sadd($this->k('user', $userId, 'sids'), $sid);
            $p->expire($this->k('user', $userId, 'sids'), $this->cfg['refresh_absolute']);
        });

        return [
            'access_token' => $this->signAccess($userId, $sid),
            'refresh_token' => $refresh,
            'expires_in' => $this->cfg['access_ttl'],
            'sid' => $sid,
            'refresh_ttl' => $ttl,
        ];
    }

    private function enforceMaxSessions(int $userId): void
    {
        $key = $this->k('user', $userId, 'sids');
        $sids = $this->redis()->smembers($key);
        $alive = [];
        foreach ($sids as $sid) {
            $created = $this->redis()->hget($this->k('sess', $sid), 'created_at');
            if ($created === null || $created === false) {
                $this->redis()->srem($key, $sid);
            } else {
                $alive[$sid] = (int) $created;
            }
        }
        asort($alive);
        while (count($alive) >= $this->cfg['max_sessions']) {
            $oldest = array_key_first($alive);
            $this->destroySession($userId, $oldest);
            unset($alive[$oldest]);
        }
    }

    private function signAccess(int $userId, string $sid): string
    {
        $now = time();

        return JWT::encode([
            'iss' => config('app.url'), 'aud' => $this->aud, 'sub' => (string) $userId, 'sid' => $sid,
            'jti' => bin2hex(random_bytes(12)), 'iat' => $now, 'exp' => $now + $this->cfg['access_ttl'],
        ], $this->cfg['secret'], 'HS256', $this->cfg['kid']);
    }

    /** Xác thực access token → claims. Ném ApiException với mã lỗi cho FE. */
    public function authenticate(string $jwt): array
    {
        try {
            $claims = (array) JWT::decode($jwt, new Key($this->cfg['secret'], 'HS256'));
        } catch (ExpiredException) {
            throw ApiException::unauthorized('TOKEN_EXPIRED', 'Phiên truy cập đã hết hạn');
        } catch (\Throwable) {
            throw ApiException::unauthorized('TOKEN_INVALID', 'Token không hợp lệ');
        }

        if (($claims['aud'] ?? null) !== $this->aud || ($claims['iss'] ?? null) !== config('app.url')) {
            throw ApiException::unauthorized('TOKEN_INVALID', 'Token không hợp lệ');
        }

        [$exists, $denied, $revokedBefore] = $this->redis()->pipeline(function ($p) use ($claims) {
            $p->exists($this->k('sess', $claims['sid']));
            $p->exists($this->k('deny', $claims['jti']));
            $p->get($this->k('revoked_before', $claims['sub']));
        });

        if (! $exists || $denied || ($revokedBefore && (int) $claims['iat'] < (int) $revokedBefore)) {
            throw ApiException::unauthorized('SESSION_REVOKED', 'Phiên đăng nhập đã bị thu hồi');
        }

        return $claims;
    }

    private const REFRESH_LUA = <<<'LUA'
local sess, rtOld, usedOld, rtNew, usedNew = KEYS[1], KEYS[2], KEYS[3], KEYS[4], KEYS[5]
local sha, newsha, now, grace, ttl, idle, sid = ARGV[1], ARGV[2], tonumber(ARGV[3]), tonumber(ARGV[4]), tonumber(ARGV[5]), tonumber(ARGV[6]), ARGV[7]
if redis.call('EXISTS', sess) == 0 then return {'ERR', 'REFRESH_INVALID'} end
local cur = redis.call('HGET', sess, 'refresh_hash')
local prev = redis.call('HGET', sess, 'prev_refresh_hash')
local rotated = tonumber(redis.call('HGET', sess, 'rotated_at') or '0')
local absolute = tonumber(redis.call('HGET', sess, 'absolute_exp'))
local last = tonumber(redis.call('HGET', sess, 'last_used_at'))
if sha == cur then
  if now >= absolute or (idle > 0 and now - last > idle) then
    redis.call('DEL', sess, rtOld)
    return {'ERR', 'REFRESH_INVALID'}
  end
  redis.call('SET', rtNew, sid, 'EX', ttl)
  redis.call('SET', usedOld, sid, 'EX', ttl)
  redis.call('DEL', rtOld)
  redis.call('HSET', sess, 'refresh_hash', newsha, 'prev_refresh_hash', sha, 'rotated_at', now, 'last_used_at', now)
  redis.call('EXPIRE', sess, ttl)
  return {'OK', 'ROTATED'}
end
if sha == prev and now - rotated <= grace then return {'OK', 'GRACE'} end
return {'ERR', 'REFRESH_REUSED'}
LUA;

    /** @return array{access_token:string,refresh_token:?string,expires_in:int,user_id:int,refresh_ttl:int} */
    public function refresh(?string $refreshToken): array
    {
        if (! $refreshToken) {
            throw ApiException::unauthorized('REFRESH_INVALID', 'Vui lòng đăng nhập lại');
        }
        $sha = hash('sha256', $refreshToken);
        $sid = $this->redis()->get($this->k('rt', $sha)) ?: $this->redis()->get($this->k('used', $sha));
        if (! $sid) {
            throw ApiException::unauthorized('REFRESH_INVALID', 'Vui lòng đăng nhập lại');
        }

        $sess = $this->redis()->hgetall($this->k('sess', $sid));
        if (! $sess) {
            throw ApiException::unauthorized('REFRESH_INVALID', 'Vui lòng đăng nhập lại');
        }
        $userId = (int) $sess['user_id'];
        $now = time();
        $ttl = max(1, min($this->cfg['refresh_ttl'], (int) $sess['absolute_exp'] - $now));
        $new = bin2hex(random_bytes(32));
        $newsha = hash('sha256', $new);

        $res = $this->redis()->eval(self::REFRESH_LUA, 5,
            $this->k('sess', $sid), $this->k('rt', $sha), $this->k('used', $sha), $this->k('rt', $newsha), $this->k('used', $newsha),
            $sha, $newsha, $now, config('shoe.auth.refresh_grace'), $ttl,
            (int) ($this->cfg['idle_ttl'] ?? 0), $sid);

        if ($res[0] === 'ERR') {
            if ($res[1] === 'REFRESH_REUSED') {
                // Nghi bị đánh cắp: hủy cả phiên
                $this->destroySession($userId, $sid);
                logger()->warning('Refresh token reuse detected', ['aud' => $this->aud, 'user' => $userId, 'sid' => $sid]);
                throw ApiException::unauthorized('REFRESH_REUSED', 'Phát hiện bất thường, vui lòng đăng nhập lại');
            }
            throw ApiException::unauthorized($res[1], 'Vui lòng đăng nhập lại');
        }

        return [
            'access_token' => $this->signAccess($userId, $sid),
            'refresh_token' => $res[1] === 'ROTATED' ? $new : null,
            'expires_in' => $this->cfg['access_ttl'],
            'user_id' => $userId,
            'refresh_ttl' => $ttl,
        ];
    }

    public function destroySession(int $userId, string $sid): void
    {
        $r = $this->redis();
        $s = $r->hgetall($this->k('sess', $sid));
        $r->pipeline(function ($p) use ($s, $sid, $userId) {
            foreach (['refresh_hash', 'prev_refresh_hash'] as $f) {
                if (! empty($s[$f])) {
                    $p->del($this->k('rt', $s[$f]));
                }
            }
            $p->del($this->k('sess', $sid));
            $p->srem($this->k('user', $userId, 'sids'), $sid);
        });
    }

    /** Đăng xuất phiên hiện tại: hủy phiên + chặn access token đang dùng. */
    public function logout(array $claims): void
    {
        $this->destroySession((int) $claims['sub'], $claims['sid']);
        $remain = max(1, (int) $claims['exp'] - time());
        $this->redis()->setex($this->k('deny', $claims['jti']), $remain, 1);
    }

    /** Đăng xuất mọi thiết bị / đổi mật khẩu / khóa tài khoản. */
    public function revokeAll(int $userId): void
    {
        foreach ($this->redis()->smembers($this->k('user', $userId, 'sids')) as $sid) {
            $this->destroySession($userId, $sid);
        }
        $this->redis()->setex($this->k('revoked_before', $userId), 90 * 86400, time());
    }

    public function sessions(int $userId, ?string $currentSid = null): array
    {
        $out = [];
        foreach ($this->redis()->smembers($this->k('user', $userId, 'sids')) as $sid) {
            $s = $this->redis()->hgetall($this->k('sess', $sid));
            if ($s) {
                $out[] = ['id' => $sid, 'ip' => $s['ip'], 'user_agent' => $s['user_agent'], 'created_at' => (int) $s['created_at'],
                    'last_used_at' => (int) $s['last_used_at'], 'current' => $sid === $currentSid];
            }
        }

        return $out;
    }

    public function sessionBelongsTo(int $userId, string $sid): bool
    {
        return (bool) $this->redis()->sismember($this->k('user', $userId, 'sids'), $sid);
    }

    // ---- Khóa đăng nhập sai ----
    public function loginLocked(string $ip, string $identifier): bool
    {
        return (int) $this->redis()->get("auth:rl:login:{$this->aud}:{$ip}:".sha1($identifier)) >= config('shoe.auth.login_max_attempts');
    }

    public function loginFailed(string $ip, string $identifier): void
    {
        $key = "auth:rl:login:{$this->aud}:{$ip}:".sha1($identifier);
        $this->redis()->incr($key);
        $this->redis()->expire($key, config('shoe.auth.login_lock_seconds'));
    }

    public function loginSucceeded(string $ip, string $identifier): void
    {
        $this->redis()->del("auth:rl:login:{$this->aud}:{$ip}:".sha1($identifier));
    }

    // ---- Challenge 2FA / token một lần (reset mật khẩu, xác thực email) ----
    public function putOnce(string $prefix, string $value, int $ttl): string
    {
        $token = bin2hex(random_bytes(24));
        $this->redis()->setex("{$prefix}:".hash('sha256', $token), $ttl, $value);

        return $token;
    }

    public function takeOnce(string $prefix, string $token): ?string
    {
        $key = "{$prefix}:".hash('sha256', $token);
        $v = $this->redis()->get($key);
        if ($v !== null) {
            $this->redis()->del($key);
        }

        return $v ?: null;
    }
}

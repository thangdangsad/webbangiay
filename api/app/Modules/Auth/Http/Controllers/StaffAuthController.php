<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Services\TokenService;
use App\Modules\Auth\Totp;
use App\Modules\User\Models\StaffUser;
use App\Support\ApiException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class StaffAuthController
{
    use Concerns;

    private function svc(): TokenService
    {
        return TokenService::for('staff');
    }

    public function login(Request $r)
    {
        $v = $r->validate(['email' => 'required|email', 'password' => 'required|string']);
        $svc = $this->svc();
        $id = strtolower($v['email']);
        if ($svc->loginLocked($r->ip(), $id)) {
            throw new ApiException('LOGIN_LOCKED', 'Đăng nhập sai quá nhiều lần, vui lòng thử lại sau 15 phút', 429);
        }
        $staff = StaffUser::with('role')->where('email', $id)->first();
        if (! $staff || ! Hash::check($v['password'], $staff->password_hash)) {
            $svc->loginFailed($r->ip(), $id);
            throw new ApiException('INVALID_CREDENTIALS', 'Email hoặc mật khẩu không đúng', 401);
        }
        if ($staff->status !== 'active') {
            throw ApiException::forbidden('Tài khoản đã bị khóa');
        }
        $needs2fa = $staff->two_factor_secret
            || (config('shoe.auth.staff_2fa_enforced') && in_array($staff->role->code, config('shoe.auth.staff_2fa_roles'), true));
        if ($needs2fa) {
            if (! $staff->two_factor_secret) {
                throw new ApiException('2FA_NOT_SET', 'Tài khoản yêu cầu 2FA nhưng chưa thiết lập, liên hệ quản trị', 403);
            }
            $challenge = $svc->putOnce('auth:staff:2fa', (string) $staff->id, 300);

            return ['data' => ['requires_2fa' => true, 'challenge_token' => $challenge]];
        }

        return $this->complete($staff, $r);
    }

    public function verify2fa(Request $r)
    {
        $v = $r->validate(['challenge_token' => 'required|string', 'code' => 'required|digits:6']);
        $sid = $this->svc()->takeOnce('auth:staff:2fa', $v['challenge_token']);
        $staff = $sid ? StaffUser::with('role')->find($sid) : null;
        if (! $staff || ! Totp::verify($staff->two_factor_secret, $v['code'])) {
            throw new ApiException('INVALID_2FA', 'Mã xác thực không đúng hoặc đã hết hạn', 401);
        }

        return $this->complete($staff, $r);
    }

    private function complete(StaffUser $staff, Request $r)
    {
        $svc = $this->svc();
        $svc->loginSucceeded($r->ip(), strtolower($staff->email));
        $staff->forceFill(['last_login_at' => now()])->save();
        $tokens = $svc->issue($staff->id, $this->device($r));

        return $this->tokenResponse($svc, $tokens, 200, ['user' => $this->payload($staff)]);
    }

    public function refresh(Request $r)
    {
        $svc = $this->svc();
        $t = $svc->refresh($r->cookie($svc->cfg('cookie')));
        $staff = StaffUser::with('role')->find($t['user_id']);
        if (! $staff || $staff->status !== 'active') {
            throw ApiException::unauthorized('SESSION_REVOKED', 'Tài khoản không khả dụng');
        }

        return $this->tokenResponse($svc, $t, 200, ['user' => $this->payload($staff)]);
    }

    public function logout(Request $r)
    {
        $svc = $this->svc();
        try {
            if ($r->bearerToken()) {
                $svc->logout($svc->authenticate($r->bearerToken()));
            }
        } catch (ApiException) {
        }

        return $this->clearCookie($svc, response()->json(['data' => ['ok' => true]]));
    }

    public function logoutAll(Request $r)
    {
        $this->svc()->revokeAll(Auth::guard('staff')->id());

        return $this->clearCookie($this->svc(), response()->json(['data' => ['ok' => true]]));
    }

    public function sessions(Request $r)
    {
        $c = $r->attributes->get('jwt.staff');

        return ['data' => $this->svc()->sessions((int) $c['sub'], $c['sid'])];
    }

    public function destroySession(Request $r, string $sid)
    {
        $uid = Auth::guard('staff')->id();
        if (! $this->svc()->sessionBelongsTo($uid, $sid)) {
            throw ApiException::notFound();
        }
        $this->svc()->destroySession($uid, $sid);

        return response()->noContent();
    }

    public function me(Request $r)
    {
        return ['data' => $this->payload($r->user('staff')->loadMissing('role'))];
    }

    public function setup2fa(Request $r)
    {
        $s = $r->user('staff');
        $secret = Totp::generateSecret();
        $s->forceFill(['two_factor_secret' => null])->save();
        $this->svc()->putOnce('auth:staff:2fa_setup', "{$s->id}:$secret", 600); // hiển thị secret cho người dùng nhập vào app
        cache()->put("staff2fa:{$s->id}", $secret, 600);

        return ['data' => ['secret' => $secret, 'otpauth_uri' => Totp::uri($secret, $s->email)]];
    }

    public function enable2fa(Request $r)
    {
        $s = $r->user('staff');
        $r->validate(['code' => 'required|digits:6']);
        $secret = cache()->get("staff2fa:{$s->id}");
        if (! $secret || ! Totp::verify($secret, $r->input('code'))) {
            throw ApiException::invalid('Mã xác thực không đúng');
        }
        $s->forceFill(['two_factor_secret' => $secret])->save();
        cache()->forget("staff2fa:{$s->id}");

        return ['data' => ['enabled' => true]];
    }

    private function payload(StaffUser $s): array
    {
        return [
            'id' => $s->id, 'email' => $s->email, 'full_name' => $s->full_name,
            'role' => ['code' => $s->role->code, 'name' => $s->role->name],
            'permissions' => $s->permissions(), 'two_factor_enabled' => (bool) $s->two_factor_secret,
        ];
    }
}

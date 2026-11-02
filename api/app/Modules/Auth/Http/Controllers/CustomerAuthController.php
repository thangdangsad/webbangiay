<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Services\TokenService;
use App\Modules\Cart\Services\CartService;
use App\Modules\Notification\Notifier;
use App\Modules\User\Models\User;
use App\Support\ApiException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CustomerAuthController
{
    use Concerns;

    private function svc(): TokenService
    {
        return TokenService::for('customer');
    }

    public function register(Request $r)
    {
        $v = $r->validate([
            'full_name' => 'required|string|max:120',
            'email' => 'nullable|email|max:190|unique:users,email|required_without:phone',
            'phone' => ['nullable', 'regex:/^(0|\+84)\d{9}$/', 'unique:users,phone', 'required_without:email'],
            'password' => 'required|string|min:8|max:100',
        ]);
        $user = User::create([
            'full_name' => $v['full_name'],
            'email' => isset($v['email']) ? strtolower($v['email']) : null,
            'phone' => isset($v['phone']) ? Notifier::normalizePhone($v['phone']) : null,
            'password_hash' => Hash::make($v['password']),
        ]);
        if ($user->email) {
            $token = $this->svc()->putOnce('verify_email', (string) $user->id, 86400);
            Notifier::mail($user->email, 'Xác thực email SoleHouse', "Xin chào {$user->full_name},\nXác thực email: ".config('shoe.frontends.storefront')."/xac-thuc-email?token=$token");
        }

        return $this->login($r->merge(['identifier' => $user->email ?? $user->phone]), 201);
    }

    public function login(Request $r, int $status = 200)
    {
        $v = $r->validate(['identifier' => 'required|string', 'password' => 'required|string']);
        $svc = $this->svc();
        $id = strtolower(trim($v['identifier']));
        if ($svc->loginLocked($r->ip(), $id)) {
            throw new ApiException('LOGIN_LOCKED', 'Đăng nhập sai quá nhiều lần, vui lòng thử lại sau 15 phút', 429);
        }
        $user = User::where('email', $id)->orWhere('phone', Notifier::normalizePhone($id))->first();
        if (! $user || ! $user->password_hash || ! Hash::check($v['password'], $user->password_hash)) {
            $svc->loginFailed($r->ip(), $id);
            throw new ApiException('INVALID_CREDENTIALS', 'Email/SĐT hoặc mật khẩu không đúng', 401);
        }
        if ($user->isBlocked()) {
            throw ApiException::forbidden('Tài khoản đã bị khóa');
        }
        $svc->loginSucceeded($r->ip(), $id);
        if (Hash::needsRehash($user->password_hash)) {
            $user->update(['password_hash' => Hash::make($v['password'])]);
        }

        return $this->startSession($user, $r, $status);
    }

    private function startSession(User $user, Request $r, int $status = 200)
    {
        $svc = $this->svc();
        $tokens = $svc->issue($user->id, $this->device($r));
        if ($cartToken = $r->header('X-Cart-Token')) {
            app(CartService::class)->mergeGuestCart($user, $cartToken);
        }

        return $this->tokenResponse($svc, $tokens, $status, ['user' => $this->userPayload($user)]);
    }

    /** Đăng nhập Google: FE gửi ID token (credential) từ Google Identity Services. */
    public function google(Request $r)
    {
        $clientId = config('shoe.google.client_id');
        if (! $clientId) {
            throw new ApiException('GOOGLE_DISABLED', 'Đăng nhập Google chưa được cấu hình', 503);
        }
        $credential = $r->validate(['credential' => 'required|string'])['credential'];
        $info = Http::timeout(10)->get('https://oauth2.googleapis.com/tokeninfo', ['id_token' => $credential])->json();
        if (($info['aud'] ?? null) !== $clientId || ! in_array($info['iss'] ?? '', ['accounts.google.com', 'https://accounts.google.com'], true)
            || ($info['email_verified'] ?? 'false') !== 'true' || (int) ($info['exp'] ?? 0) < time() || empty($info['email'])) {
            throw new ApiException('GOOGLE_INVALID', 'Không xác thực được tài khoản Google', 401);
        }
        $user = User::where('email', strtolower($info['email']))->first();
        $created = false;
        if (! $user) {
            $user = User::create(['email' => strtolower($info['email']), 'full_name' => $info['name'] ?? $info['email'], 'password_hash' => null, 'email_verified_at' => now()]);
            $created = true;
        } elseif (! $user->email_verified_at) {
            $user->update(['email_verified_at' => now()]);
        }
        if ($user->isBlocked()) {
            throw ApiException::forbidden('Tài khoản đã bị khóa');
        }

        return $this->startSession($user, $r, $created ? 201 : 200);
    }

    public function refresh(Request $r)
    {
        $svc = $this->svc();
        $t = $svc->refresh($r->cookie($svc->cfg('cookie')));
        $user = User::find($t['user_id']);
        if (! $user || $user->isBlocked()) {
            throw ApiException::unauthorized('SESSION_REVOKED', 'Tài khoản không khả dụng');
        }

        return $this->tokenResponse($svc, $t, 200, ['user' => $this->userPayload($user)]);
    }

    public function logout(Request $r)
    {
        $svc = $this->svc();
        $claims = null;
        try {
            if ($r->bearerToken()) {
                $claims = $svc->authenticate($r->bearerToken());
            }
        } catch (ApiException) {
        }
        if ($claims) {
            $svc->logout($claims);
        } elseif ($rt = $r->cookie($svc->cfg('cookie'))) {
            try {
                $t = $svc->refresh($rt); // chỉ để xác định phiên rồi hủy
                $c = $svc->authenticate($t['access_token']);
                $svc->logout($c);
            } catch (ApiException) {
            }
        }

        return $this->clearCookie($svc, response()->json(['data' => ['ok' => true]]));
    }

    public function logoutAll(Request $r)
    {
        $svc = $this->svc();
        $svc->revokeAll(Auth::guard('customer')->id());

        return $this->clearCookie($svc, response()->json(['data' => ['ok' => true]]));
    }

    public function sessions(Request $r)
    {
        $claims = $r->attributes->get('jwt.customer');

        return ['data' => $this->svc()->sessions((int) $claims['sub'], $claims['sid'])];
    }

    public function destroySession(Request $r, string $sid)
    {
        $uid = Auth::guard('customer')->id();
        if (! $this->svc()->sessionBelongsTo($uid, $sid)) {
            throw ApiException::notFound();
        }
        $this->svc()->destroySession($uid, $sid);

        return response()->noContent();
    }

    public function forgotPassword(Request $r)
    {
        $v = $r->validate(['email' => 'required|email']);
        $user = User::where('email', strtolower($v['email']))->first();
        if ($user) {
            $token = $this->svc()->putOnce('pwd_reset', (string) $user->id, 1800);
            Notifier::mail($user->email, 'Đặt lại mật khẩu SoleHouse', 'Đặt lại mật khẩu (hết hạn sau 30 phút): '.config('shoe.frontends.storefront')."/quen-mat-khau?token=$token");
        }

        return ['data' => ['ok' => true]]; // không tiết lộ email có tồn tại hay không
    }

    public function resetPassword(Request $r)
    {
        $v = $r->validate(['token' => 'required|string', 'password' => 'required|string|min:8|max:100']);
        $uid = $this->svc()->takeOnce('pwd_reset', $v['token']);
        $user = $uid ? User::find($uid) : null;
        if (! $user) {
            throw ApiException::invalid('Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn');
        }
        $user->update(['password_hash' => Hash::make($v['password'])]);
        $this->svc()->revokeAll($user->id);

        return ['data' => ['ok' => true]];
    }

    public function verifyEmail(Request $r)
    {
        $uid = $this->svc()->takeOnce('verify_email', (string) $r->input('token'));
        $user = $uid ? User::find($uid) : null;
        if (! $user) {
            throw ApiException::invalid('Liên kết xác thực không hợp lệ hoặc đã hết hạn');
        }
        $user->update(['email_verified_at' => now()]);

        return ['data' => ['ok' => true]];
    }

    public function me(Request $r)
    {
        return ['data' => $this->userPayload($r->user('customer'))];
    }

    public function updateMe(Request $r)
    {
        $u = $r->user('customer');
        $v = $r->validate([
            'full_name' => 'sometimes|string|max:120',
            'phone' => ['sometimes', 'nullable', 'regex:/^(0|\+84)\d{9}$/', Rule::unique('users', 'phone')->ignore($u->id)],
            'gender' => 'sometimes|nullable|in:male,female,other',
            'birthday' => 'sometimes|nullable|date',
        ]);
        if (! empty($v['phone'])) {
            $v['phone'] = Notifier::normalizePhone($v['phone']);
        }
        $u->update($v);

        return ['data' => $this->userPayload($u->refresh())];
    }

    public function changePassword(Request $r)
    {
        $u = $r->user('customer');
        $v = $r->validate(['current_password' => $u->password_hash ? 'required|string' : 'nullable|string', 'password' => 'required|string|min:8|max:100']);
        if ($u->password_hash && ! Hash::check($v['current_password'], (string) $u->password_hash)) {
            throw ApiException::invalid('Mật khẩu hiện tại không đúng');
        }
        $u->update(['password_hash' => Hash::make($v['password'])]);
        $this->svc()->revokeAll($u->id);

        return $this->clearCookie($this->svc(), response()->json(['data' => ['ok' => true, 'relogin' => true]]));
    }

    private function userPayload(User $u): array
    {
        return [
            'id' => $u->id, 'full_name' => $u->full_name, 'email' => $u->email, 'phone' => $u->phone,
            'gender' => $u->gender, 'birthday' => $u->birthday?->toDateString(), 'email_verified' => (bool) $u->email_verified_at, 'has_password' => (bool) $u->password_hash,
        ];
    }
}

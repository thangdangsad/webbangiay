<?php

namespace App\Modules\Auth\Http\Middleware;

use App\Modules\Auth\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Ghi audit log cho mọi thao tác ghi của admin (POST/PUT/PATCH/DELETE) thành công. */
class AuditAdminWrites
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true) && $response->getStatusCode() < 400) {
            $staff = Auth::guard('staff')->user();
            if ($staff && ! str_contains($request->path(), 'admin/auth/')) {
                $segments = explode('/', trim(preg_replace('#^api/v1/admin/#', '', $request->path()), '/'));
                $payload = collect($request->except(['password', 'password_confirmation', 'code', 'file']))->all();
                AuditLogger::log($staff->id, $request->method(), $segments[0] ?? 'admin', isset($segments[1]) && ctype_digit($segments[1]) ? (int) $segments[1] : null, null, [
                    'path' => $request->path(), 'input' => $payload,
                ], $request->ip());
            }
        }

        return $response;
    }
}

<?php

namespace App\Modules\Auth\Http\Middleware;

use App\Support\ApiException;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** perm:product:write — kiểm tra quyền RBAC (resource:action) của nhân viên. Nhiều quyền: OR bằng dấu '|'. */
class StaffCan
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        $user = Auth::guard('staff')->user();
        if (! $user) {
            throw ApiException::unauthorized();
        }
        foreach (explode('|', $permission) as $p) {
            if ($user->hasPermission($p)) {
                return $next($request);
            }
        }
        throw ApiException::forbidden();
    }
}

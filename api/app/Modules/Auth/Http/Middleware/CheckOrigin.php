<?php

namespace App\Modules\Auth\Http\Middleware;

use App\Support\ApiException;
use Closure;
use Illuminate\Http\Request;

/** Chống CSRF cho endpoint dùng cookie (refresh/logout): kiểm tra Origin + header X-Requested-With. */
class CheckOrigin
{
    public function handle(Request $request, Closure $next)
    {
        $origin = $request->headers->get('Origin');
        $allowed = array_filter(explode(',', env('CORS_ALLOWED_ORIGINS', '')));
        if ($origin && ! in_array($origin, $allowed, true)) {
            throw ApiException::forbidden('Origin không hợp lệ');
        }
        if ($request->headers->get('X-Requested-With') !== 'XMLHttpRequest') {
            throw ApiException::forbidden('Thiếu header X-Requested-With');
        }

        return $next($request);
    }
}

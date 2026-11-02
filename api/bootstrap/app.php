<?php

use App\Support\ApiException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        apiPrefix: 'api/v1',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'perm' => App\Modules\Auth\Http\Middleware\StaffCan::class,
            'audit' => App\Modules\Auth\Http\Middleware\AuditAdminWrites::class,
            'origin' => App\Modules\Auth\Http\Middleware\CheckOrigin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => true);

        $error = fn (string $code, string $message, int $status, array $details = []) => response()->json(
            ['error' => ['code' => $code, 'message' => $message, 'details' => $details]], $status
        );

        $exceptions->render(fn (ApiException $e) => $error($e->errorCode, $e->getMessage(), $e->status, $e->details));
        $exceptions->render(fn (ValidationException $e) => $error('VALIDATION', collect($e->errors())->flatten()->first() ?? 'Dữ liệu không hợp lệ', 422, $e->errors()));
        $exceptions->render(fn (AuthenticationException $e) => $error('UNAUTHENTICATED', 'Vui lòng đăng nhập', 401));
        $exceptions->render(fn (AuthorizationException $e) => $error('FORBIDDEN', 'Bạn không có quyền thực hiện thao tác này', 403));
        $exceptions->render(fn (ModelNotFoundException $e) => $error('NOT_FOUND', 'Không tìm thấy dữ liệu', 404));
        $exceptions->render(fn (NotFoundHttpException $e) => $error('NOT_FOUND', 'Không tìm thấy', 404));
        $exceptions->render(fn (TooManyRequestsHttpException $e) => $error('TOO_MANY_REQUESTS', 'Thao tác quá nhanh, vui lòng thử lại sau', 429));
        $exceptions->render(fn (HttpExceptionInterface $e) => $error('HTTP_'.$e->getStatusCode(), $e->getMessage() ?: 'Lỗi', $e->getStatusCode()));
    })->create();

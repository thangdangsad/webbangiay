<?php

use App\Modules\Auth\Http\Controllers\AdminSystemController;
use App\Modules\Auth\Http\Controllers\StaffAuthController;
use App\Modules\Catalog\Http\Controllers\AdminProductController;
use App\Modules\Catalog\Http\Controllers\AdminResourceController;
use App\Modules\Catalog\Http\Controllers\ResourceRegistry;
use App\Modules\Inventory\Http\Controllers\AdminInventoryController;
use App\Modules\Order\Http\Controllers\AdminOrderController;
use App\Modules\Report\Http\Controllers\ReportController;
use App\Modules\User\Http\Controllers\AdminCustomerController;
use Illuminate\Support\Facades\Route;

/* ---------------- Admin (guard: staff + RBAC) ---------------- */
Route::prefix('admin')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('login', [StaffAuthController::class, 'login'])->middleware('throttle:10,1,alogin');
        Route::post('2fa/verify', [StaffAuthController::class, 'verify2fa'])->middleware('throttle:10,1,a2fa');
        Route::middleware('origin')->group(function () {
            Route::post('refresh', [StaffAuthController::class, 'refresh']);
            Route::post('logout', [StaffAuthController::class, 'logout']);
        });
        Route::middleware('auth:staff')->group(function () {
            Route::post('logout-all', [StaffAuthController::class, 'logoutAll']);
            Route::get('sessions', [StaffAuthController::class, 'sessions']);
            Route::delete('sessions/{sid}', [StaffAuthController::class, 'destroySession']);
            Route::post('2fa/setup', [StaffAuthController::class, 'setup2fa']);
            Route::post('2fa/enable', [StaffAuthController::class, 'enable2fa']);
        });
    });

    Route::middleware(['auth:staff', 'audit'])->group(function () {
        Route::get('me', [StaffAuthController::class, 'me']);

        Route::get('dashboard/summary', [ReportController::class, 'dashboard'])->middleware('perm:dashboard:read');

        // Sản phẩm
        Route::middleware('perm:product:read')->group(function () {
            Route::get('products', [AdminProductController::class, 'index']);
            Route::get('products/export', [AdminProductController::class, 'export']);
            Route::get('products/{id}', [AdminProductController::class, 'show'])->whereNumber('id');
        });
        Route::middleware('perm:product:write')->group(function () {
            Route::post('products', [AdminProductController::class, 'store']);
            Route::post('products/bulk', [AdminProductController::class, 'bulk']);
            Route::post('products/import', [AdminProductController::class, 'import']);
            Route::put('products/{id}', [AdminProductController::class, 'update']);
            Route::delete('products/{id}', [AdminProductController::class, 'destroy']);
            Route::patch('products/{id}/status', [AdminProductController::class, 'status']);
            Route::put('products/{id}/variants', [AdminProductController::class, 'syncVariants']);
            Route::post('uploads', [AdminProductController::class, 'upload']);
            Route::delete('uploads', [AdminProductController::class, 'deleteUpload']);
        });

        // Danh mục · thương hiệu · thuộc tính
        foreach (['categories', 'brands', 'colors', 'sizes'] as $res) {
            Route::get($res, [AdminResourceController::class, 'index'])->defaults('resource', $res)->middleware('perm:product:read');
            Route::middleware('perm:product:write')->group(function () use ($res) {
                Route::post($res, [AdminResourceController::class, 'store'])->defaults('resource', $res);
                Route::put("$res/{id}", [AdminResourceController::class, 'update'])->defaults('resource', $res);
                Route::delete("$res/{id}", [AdminResourceController::class, 'destroy'])->defaults('resource', $res);
            });
        }

        // Kho
        Route::middleware('perm:inventory:read')->group(function () {
            Route::get('inventory', [AdminInventoryController::class, 'index']);
            Route::get('inventory/movements', [AdminInventoryController::class, 'movements']);
            Route::get('inventory/receipts', [AdminInventoryController::class, 'receipts']);
        });
        Route::middleware('perm:inventory:write')->group(function () {
            Route::post('inventory/receipts', [AdminInventoryController::class, 'receipt']);
            Route::post('inventory/adjust', [AdminInventoryController::class, 'adjust']);
            Route::patch('inventory/{variantId}/threshold', [AdminInventoryController::class, 'threshold']);
        });

        // Đơn hàng
        Route::middleware('perm:order:read')->group(function () {
            Route::get('orders', [AdminOrderController::class, 'index']);
            Route::get('orders/{id}', [AdminOrderController::class, 'show'])->whereNumber('id');
            Route::get('orders/{id}/print', [AdminOrderController::class, 'print']);
        });
        Route::post('orders/{id}/transition', [AdminOrderController::class, 'transition']);
        Route::post('orders/{id}/cancel', [AdminOrderController::class, 'cancel']);
        Route::patch('orders/{id}/internal-note', [AdminOrderController::class, 'internalNote'])->middleware('perm:order:read');
        Route::post('orders/{id}/shipment', [AdminOrderController::class, 'shipment'])->middleware('perm:order:fulfil');
        Route::middleware('perm:payment:manage')->group(function () {
            Route::post('orders/{id}/payments', [AdminOrderController::class, 'recordPayment']);
            Route::post('orders/{id}/payments/momo/query', [AdminOrderController::class, 'momoQuery']);
            Route::post('orders/{id}/payments/momo/refund', [AdminOrderController::class, 'momoRefund']);
        });

        // Đổi trả
        Route::get('returns', [AdminOrderController::class, 'returnsIndex'])->middleware('perm:return:read');
        Route::post('returns/{id}/{action}', [AdminOrderController::class, 'returnAction'])->whereIn('action', ['approve', 'reject', 'receive', 'refund']);

        // Khách hàng
        Route::middleware('perm:customer:read')->group(function () {
            Route::get('customers', [AdminCustomerController::class, 'index']);
            Route::get('customers/{id}', [AdminCustomerController::class, 'show']);
        });
        Route::patch('customers/{id}/status', [AdminCustomerController::class, 'status'])->middleware('perm:customer:write');

        // Marketing · CMS · vận chuyển
        $crud = ['coupons' => 'marketing:manage', 'promotions' => 'marketing:manage', 'banners' => 'marketing:manage', 'pages' => 'content:manage', 'shipping-rates' => 'settings:manage'];
        foreach ($crud as $res => $perm) {
            Route::middleware("perm:$perm")->group(function () use ($res) {
                Route::get($res, [AdminResourceController::class, 'index'])->defaults('resource', $res);
                Route::get("$res/{id}", [AdminResourceController::class, 'show'])->defaults('resource', $res);
                Route::post($res, [AdminResourceController::class, 'store'])->defaults('resource', $res);
                Route::put("$res/{id}", [AdminResourceController::class, 'update'])->defaults('resource', $res);
                Route::delete("$res/{id}", [AdminResourceController::class, 'destroy'])->defaults('resource', $res);
            });
        }

        // Đánh giá sản phẩm
        Route::middleware('perm:content:manage')->group(function () {
            Route::get('reviews', [\App\Modules\Catalog\Http\Controllers\AdminReviewController::class, 'index']);
            Route::patch('reviews/{id}', [\App\Modules\Catalog\Http\Controllers\AdminReviewController::class, 'update']);
            Route::delete('reviews/{id}', [\App\Modules\Catalog\Http\Controllers\AdminReviewController::class, 'destroy']);
        });

        // Báo cáo
        Route::middleware('perm:report:read')->prefix('reports')->group(function () {
            Route::get('revenue', [ReportController::class, 'revenue']);
            Route::get('top-products', [ReportController::class, 'topProducts']);
            Route::get('inventory', [ReportController::class, 'inventory']);
            Route::get('returns', [ReportController::class, 'returns']);
        });

        // Cài đặt · nhân viên · audit
        Route::middleware('perm:settings:manage')->group(function () {
            Route::get('settings', [AdminSystemController::class, 'settings']);
            Route::put('settings', [AdminSystemController::class, 'updateSettings']);
        });
        Route::middleware('perm:staff:manage')->group(function () {
            Route::get('staff', [AdminSystemController::class, 'staffIndex']);
            Route::post('staff', [AdminSystemController::class, 'staffStore']);
            Route::put('staff/{id}', [AdminSystemController::class, 'staffUpdate']);
            Route::get('roles', [AdminSystemController::class, 'roles']);
            Route::put('roles/{id}', [AdminSystemController::class, 'roleUpdate']);
        });
        Route::get('audit-logs', [AdminSystemController::class, 'auditLogs'])->middleware('perm:audit:read');
    });
});

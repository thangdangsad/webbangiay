<?php

namespace App\Console\Commands;

use App\Modules\Notification\Notifier;
use App\Modules\User\Models\StaffUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LowStockAlert extends Command
{
    protected $signature = 'inventory:low-stock-alert';

    protected $description = 'Gửi email cảnh báo tồn kho thấp cho Manager và Kho';

    public function handle(): int
    {
        $rows = DB::table('inventory as i')->join('product_variants as v', 'v.id', '=', 'i.variant_id')->join('products as p', 'p.id', '=', 'v.product_id')
            ->whereNull('p.deleted_at')->where('p.status', 'active')->whereRaw('i.on_hand - i.reserved <= i.low_stock_threshold')
            ->get(['v.sku', 'p.name', DB::raw('i.on_hand - i.reserved as available')]);
        if ($rows->isEmpty()) {
            return self::SUCCESS;
        }
        $body = $rows->map(fn ($r) => "{$r->sku} · {$r->name}: còn {$r->available}")->implode("\n");
        StaffUser::whereHas('role', fn ($q) => $q->whereIn('code', ['manager', 'warehouse']))->each(fn ($s) => Notifier::mail($s->email, 'Cảnh báo tồn kho thấp', $body));
        $this->info($rows->count().' biến thể sắp hết hàng.');

        return self::SUCCESS;
    }
}

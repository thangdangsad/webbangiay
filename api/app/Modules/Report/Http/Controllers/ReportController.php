<?php

namespace App\Modules\Report\Http\Controllers;

use App\Modules\Order\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController
{
    private const TZ = 'Asia/Ho_Chi_Minh';

    /** @return array{0:Carbon,1:Carbon} (UTC) */
    private function range(Request $r): array
    {
        $now = now(self::TZ);
        if ($r->filled('from')) {
            $from = Carbon::parse($r->query('from'), self::TZ)->startOfDay();
            $to = $r->filled('to') ? Carbon::parse($r->query('to'), self::TZ)->endOfDay() : $now;
        } else {
            $days = ['today' => 1, '7d' => 7, '30d' => 30, '90d' => 90][$r->query('range', '7d')] ?? 7;
            $from = $now->copy()->subDays($days - 1)->startOfDay();
            $to = $now;
        }

        return [$from->utc(), $to->utc()];
    }

    private function csv(array $head, array $rows, string $name)
    {
        $out = fopen('php://temp', 'w+');
        fputcsv($out, $head);
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
        rewind($out);

        return response("\xEF\xBB\xBF".stream_get_contents($out), 200, ['Content-Type' => 'text/csv; charset=utf-8', 'Content-Disposition' => "attachment; filename=\"$name.csv\""]);
    }

    /** Ngày đã qua đọc từ bảng tổng hợp daily_sales_summary (nếu có), hôm nay tính trực tiếp. */
    private function revenueSeries(Carbon $from, Carbon $to, ?string $groupBy = null)
    {
        $today = now(self::TZ)->toDateString();
        $summary = DB::table('daily_sales_summary')->whereBetween('date', [$from->copy()->setTimezone(self::TZ)->toDateString(), $to->copy()->setTimezone(self::TZ)->toDateString()])->get()->keyBy('date');
        $live = Order::whereBetween('placed_at', [$from, $to])->whereNotIn('status', ['cancelled'])
            ->selectRaw("DATE(CONVERT_TZ(placed_at,'+00:00','+07:00')) d, COUNT(*) orders, SUM(grand_total) revenue")->groupBy('d')->orderBy('d')->get()->keyBy('d');
        $series = [];
        for ($d = $from->copy()->setTimezone(self::TZ)->startOfDay(); $d->lte($to->copy()->setTimezone(self::TZ)); $d->addDay()) {
            $k = $d->toDateString();
            $row = ($k < $today && isset($summary[$k])) ? $summary[$k] : ($live[$k] ?? null);
            $series[] = ['date' => $k, 'orders' => (int) ($row->orders ?? 0), 'revenue' => (int) ($row->revenue ?? 0)];
        }

        return $series;
    }

    public function dashboard(Request $r)
    {
        [$from, $to] = $this->range($r);
        $valid = fn () => Order::whereBetween('placed_at', [$from, $to])->whereNotIn('status', ['cancelled']);
        $topProducts = DB::table('order_items as oi')->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->whereBetween('o.placed_at', [$from, $to])->where('o.status', '!=', 'cancelled')
            ->selectRaw('oi.product_id, oi.product_name, SUM(oi.qty) qty, SUM(oi.line_total) revenue')->groupBy('oi.product_id', 'oi.product_name')->orderByDesc('qty')->limit(5)->get();
        $low = DB::table('inventory as i')->join('product_variants as v', 'v.id', '=', 'i.variant_id')->join('products as p', 'p.id', '=', 'v.product_id')->join('sizes as s', 's.id', '=', 'v.size_id')
            ->whereNull('p.deleted_at')->whereRaw('i.on_hand - i.reserved <= i.low_stock_threshold')->orderByRaw('i.on_hand - i.reserved')->limit(8)
            ->get(['v.sku', 'p.name', 's.label as size', DB::raw('i.on_hand - i.reserved as available')]);

        return ['data' => [
            'kpi' => [
                'revenue' => (int) $valid()->sum('grand_total'), 'orders' => $valid()->count(),
                'pending_orders' => Order::where('status', 'pending')->count(),
                'new_customers' => DB::table('users')->whereBetween('created_at', [$from, $to])->count(),
            ],
            'series' => $this->revenueSeries($from, $to),
            'top_products' => $topProducts,
            'low_stock' => $low,
            'latest_orders' => Order::with('items')->orderByDesc('id')->limit(6)->get()->map(fn ($o) => app(\App\Modules\Order\Services\OrderPresenter::class)->summary($o)),
        ]];
    }

    public function revenue(Request $r)
    {
        [$from, $to] = $this->range($r);
        $series = $this->revenueSeries($from, $to);
        $byMethod = Order::whereBetween('placed_at', [$from, $to])->where('status', '!=', 'cancelled')->selectRaw('payment_method, COUNT(*) orders, SUM(grand_total) revenue')->groupBy('payment_method')->get();
        if ($r->query('format') === 'csv') {
            return $this->csv(['Ngày', 'Số đơn', 'Doanh thu'], array_map(fn ($x) => array_values($x), $series), 'revenue');
        }

        return ['data' => ['series' => $series, 'by_payment_method' => $byMethod, 'total' => array_sum(array_column($series, 'revenue')), 'orders' => array_sum(array_column($series, 'orders'))]];
    }

    public function topProducts(Request $r)
    {
        [$from, $to] = $this->range($r);
        $by = $r->query('by', 'product');
        $q = DB::table('order_items as oi')->join('orders as o', 'o.id', '=', 'oi.order_id')->whereBetween('o.placed_at', [$from, $to])->where('o.status', '!=', 'cancelled');
        if ($by === 'brand') {
            $q->join('products as p', 'p.id', '=', 'oi.product_id')->leftJoin('brands as b', 'b.id', '=', 'p.brand_id')->selectRaw("COALESCE(b.name,'Khác') name, SUM(oi.qty) qty, SUM(oi.line_total) revenue")->groupBy('b.name');
        } elseif ($by === 'category') {
            $q->join('products as p', 'p.id', '=', 'oi.product_id')->leftJoin('categories as c', 'c.id', '=', 'p.category_id')->selectRaw("COALESCE(c.name,'Khác') name, SUM(oi.qty) qty, SUM(oi.line_total) revenue")->groupBy('c.name');
        } else {
            $q->selectRaw('oi.product_name name, SUM(oi.qty) qty, SUM(oi.line_total) revenue')->groupBy('oi.product_id', 'oi.product_name');
        }
        $rows = $q->orderByDesc('qty')->limit((int) $r->query('limit', 10))->get();
        if ($r->query('format') === 'csv') {
            return $this->csv(['Tên', 'Số lượng', 'Doanh thu'], $rows->map(fn ($x) => [$x->name, $x->qty, $x->revenue])->all(), 'top');
        }

        return ['data' => $rows];
    }

    public function inventory(Request $r)
    {
        $base = DB::table('inventory as i')->join('product_variants as v', 'v.id', '=', 'i.variant_id')->join('products as p', 'p.id', '=', 'v.product_id')->whereNull('p.deleted_at');
        $value = (clone $base)->selectRaw('SUM(i.on_hand * v.price) retail_value, SUM(i.on_hand) units')->first();
        $cost = DB::table('stock_receipt_items')->selectRaw('SUM(qty * unit_cost) c')->value('c');
        $stale = (clone $base)->join('sizes as s', 's.id', '=', 'v.size_id')->where('i.on_hand', '>', 0)
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('inventory_movements as m')->whereColumn('m.variant_id', 'i.variant_id')->where('m.type', 'sale')->where('m.created_at', '>=', now()->subDays(60)))
            ->orderByDesc('i.on_hand')->limit(20)->get(['v.sku', 'p.name', 's.label as size', 'i.on_hand']);
        if ($r->query('format') === 'csv') {
            return $this->csv(['SKU', 'Sản phẩm', 'Size', 'Tồn'], $stale->map(fn ($x) => [$x->sku, $x->name, $x->size, $x->on_hand])->all(), 'inventory');
        }

        return ['data' => ['retail_value' => (int) $value->retail_value, 'units' => (int) $value->units, 'cost_basis' => (int) $cost, 'slow_moving' => $stale]];
    }

    public function returns(Request $r)
    {
        [$from, $to] = $this->range($r);
        $total = Order::whereBetween('placed_at', [$from, $to])->count();
        $cancelled = Order::whereBetween('placed_at', [$from, $to])->where('status', 'cancelled')->count();
        $returned = DB::table('returns')->whereBetween('created_at', [$from, $to])->count();
        $reasons = DB::table('returns')->whereBetween('created_at', [$from, $to])->selectRaw('reason, COUNT(*) c')->groupBy('reason')->orderByDesc('c')->limit(5)->get();

        return ['data' => [
            'orders' => $total, 'cancelled' => $cancelled, 'returns' => $returned,
            'cancel_rate' => $total ? round($cancelled * 100 / $total, 1) : 0, 'return_rate' => $total ? round($returned * 100 / $total, 1) : 0, 'top_reasons' => $reasons,
        ]];
    }
}

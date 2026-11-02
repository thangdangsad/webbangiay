<?php

namespace App\Modules\Order\Http\Controllers;

use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\ReturnRequest;
use App\Modules\Order\Services\OrderPresenter;
use App\Modules\Order\Services\OrderStateMachine;
use App\Modules\Order\Services\ReturnService;
use App\Modules\Payment\Models\Payment;
use App\Modules\Payment\Services\MomoService;
use App\Modules\Shipping\Models\Shipment;
use App\Support\ApiException;
use Illuminate\Http\Request;

class AdminOrderController
{
    public function __construct(private OrderPresenter $presenter, private OrderStateMachine $machine, private MomoService $momo, private ReturnService $returns) {}

    public function index(Request $r)
    {
        $q = Order::with('items')->orderByDesc('placed_at')->orderByDesc('id');
        if ($s = trim((string) $r->query('q'))) {
            $q->where(fn ($w) => $w->where('code', 'like', "%$s%")->orWhere('phone', 'like', '%'.ltrim($s, '0').'%')->orWhere('customer_name', 'like', "%$s%"));
        }
        foreach (['status', 'payment_status', 'payment_method'] as $f) {
            if ($r->filled($f)) {
                $q->where($f, $r->query($f));
            }
        }
        if ($r->filled('from')) {
            $q->where('placed_at', '>=', \Carbon\Carbon::parse($r->query('from'), 'Asia/Ho_Chi_Minh')->startOfDay()->utc());
        }
        if ($r->filled('to')) {
            $q->where('placed_at', '<=', \Carbon\Carbon::parse($r->query('to'), 'Asia/Ho_Chi_Minh')->endOfDay()->utc());
        }
        if ($r->query('format') === 'csv') {
            return $this->csv($q->get());
        }
        $counts = Order::selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status');
        $page = $q->paginate(min(100, (int) $r->query('limit', 20)));

        return ['data' => $page->getCollection()->map(fn ($o) => $this->presenter->summary($o))->values(),
            'meta' => ['page' => $page->currentPage(), 'limit' => $page->perPage(), 'total' => $page->total(), 'status_counts' => $counts]];
    }

    private function csv($orders)
    {
        $out = fopen('php://temp', 'w+');
        fputcsv($out, ['Mã đơn', 'Khách', 'SĐT', 'Trạng thái', 'Thanh toán', 'PT thanh toán', 'Tổng tiền', 'Ngày đặt']);
        foreach ($orders as $o) {
            fputcsv($out, [$o->code, $o->customer_name, $o->phone, $o->status, $o->payment_status, $o->payment_method, $o->grand_total, $o->placed_at]);
        }
        rewind($out);

        return response("\xEF\xBB\xBF".stream_get_contents($out), 200, ['Content-Type' => 'text/csv; charset=utf-8', 'Content-Disposition' => 'attachment; filename="orders.csv"']);
    }

    public function show(int $id)
    {
        return ['data' => $this->presenter->detail(Order::findOrFail($id), true)];
    }

    public function transition(Request $r, int $id)
    {
        $v = $r->validate(['to' => 'required|string', 'note' => 'nullable|string|max:500', 'carrier' => 'nullable|string|max:60', 'tracking_code' => 'nullable|string|max:60']);
        $this->authorizeTo($r, $v['to']);
        $o = $this->machine->transition(Order::findOrFail($id), $v['to'], 'staff', $r->user('staff')->id, $v['note'] ?? null, $v);

        return ['data' => $this->presenter->detail($o, true)];
    }

    public function cancel(Request $r, int $id)
    {
        $this->authorizeTo($r, 'cancelled');
        $o = $this->machine->transition(Order::findOrFail($id), 'cancelled', 'staff', $r->user('staff')->id, $r->input('note', 'Admin hủy đơn'));

        return ['data' => $this->presenter->detail($o, true)];
    }

    private function authorizeTo(Request $r, string $to): void
    {
        $perm = OrderStateMachine::PERMISSION[$to] ?? null;
        if (! $perm || ! $r->user('staff')->hasPermission($perm)) {
            throw ApiException::forbidden('Bạn không có quyền chuyển đơn sang trạng thái này');
        }
    }

    public function internalNote(Request $r, int $id)
    {
        $o = Order::findOrFail($id);
        $o->update(['internal_note' => $r->validate(['internal_note' => 'nullable|string|max:2000'])['internal_note']]);

        return ['data' => ['internal_note' => $o->internal_note]];
    }

    public function shipment(Request $r, int $id)
    {
        $v = $r->validate(['carrier' => 'required|string|max:60', 'tracking_code' => 'required|string|max:60']);
        $o = Order::findOrFail($id);
        $s = Shipment::firstOrNew(['order_id' => $o->id]);
        $s->fill($v + ['fee' => $o->shipping_fee])->save();

        return ['data' => ['carrier' => $s->carrier, 'tracking_code' => $s->tracking_code]];
    }

    /** Ghi nhận thanh toán thủ công (ví dụ chuyển khoản/COD đã thu). */
    public function recordPayment(Request $r, int $id)
    {
        $v = $r->validate(['amount' => 'required|integer|min:1', 'note' => 'nullable|string']);
        $o = Order::findOrFail($id);
        $p = $o->payments()->where('status', 'pending')->first() ?? new Payment(['order_id' => $o->id, 'method' => $o->payment_method]);
        $p->fill(['amount' => $v['amount'], 'status' => 'paid', 'paid_at' => now()])->save();
        $o->update(['payment_status' => $v['amount'] >= $o->grand_total ? 'paid' : 'pending']);

        return ['data' => $this->presenter->detail($o->refresh(), true)];
    }

    public function momoQuery(int $id)
    {
        $o = Order::findOrFail($id);
        $res = $this->momo->query($o);

        return ['data' => $res + ['order' => $this->presenter->detail($o->refresh(), true)]];
    }

    public function momoRefund(Request $r, int $id)
    {
        $v = $r->validate(['amount' => 'nullable|integer|min:1']);
        $o = Order::findOrFail($id);
        $this->momo->refund($o, $v['amount'] ?? $o->grand_total, 'Hoàn tiền đơn '.$o->code);

        return ['data' => $this->presenter->detail($o->refresh(), true)];
    }

    public function print(int $id)
    {
        $o = Order::with('items')->findOrFail($id);
        $rows = $o->items->map(fn ($i) => "<tr><td>{$i->product_name}<br><small>{$i->sku} · {$i->color} · size {$i->size}</small></td><td align=right>".number_format($i->unit_price, 0, ',', '.')."</td><td align=center>{$i->qty}</td><td align=right>".number_format($i->line_total, 0, ',', '.').'</td></tr>')->implode('');
        $a = $o->ship_address;
        $addr = e(implode(', ', array_filter([$a['street'] ?? '', $a['ward_name'] ?? '', $a['district_name'] ?? '', $a['province_name'] ?? ''])));
        $f = fn ($n) => number_format($n, 0, ',', '.').'đ';
        $html = "<!doctype html><meta charset=utf-8><title>{$o->code}</title><style>body{font:14px system-ui;max-width:720px;margin:24px auto}table{width:100%;border-collapse:collapse}td,th{border-bottom:1px solid #ddd;padding:8px;text-align:left}</style>"
            ."<h2>SoleHouse · Phiếu giao hàng {$o->code}</h2><p><b>".e($o->customer_name)."</b> · {$o->phone}<br>{$addr}</p>"
            ."<table><tr><th>Sản phẩm<th align=right>Đơn giá<th>SL<th align=right>Thành tiền</tr>{$rows}</table>"
            .'<p align=right>Tạm tính: '.$f($o->subtotal).'<br>Giảm giá: -'.$f($o->discount_total).'<br>Phí ship: '.$f($o->shipping_fee).'<br><b>Tổng: '.$f($o->grand_total).'</b><br>'
            .($o->payment_method === 'cod' && $o->payment_status !== 'paid' ? '<b>Thu hộ (COD): '.$f($o->grand_total).'</b>' : 'Đã thanh toán').'</p>';

        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    // ---- Đổi trả ----
    public function returnsIndex(Request $r)
    {
        $q = ReturnRequest::with(['order', 'items.orderItem', 'images'])->orderByDesc('id');
        if ($r->filled('status')) {
            $q->where('status', $r->query('status'));
        }
        $page = $q->paginate(20);

        return ['data' => $page->getCollection()->map(fn ($x) => $this->returnRow($x))->values(), 'meta' => ['page' => $page->currentPage(), 'limit' => 20, 'total' => $page->total()]];
    }

    private function returnRow(ReturnRequest $x): array
    {
        return [
            'id' => $x->id, 'code' => $x->code, 'status' => $x->status, 'reason' => $x->reason, 'refund_amount' => (int) $x->refund_amount, 'staff_note' => $x->staff_note,
            'order' => ['id' => $x->order->id, 'code' => $x->order->code, 'customer_name' => $x->order->customer_name, 'payment_method' => $x->order->payment_method, 'payment_status' => $x->order->payment_status],
            'items' => $x->items->map(fn ($i) => ['id' => $i->id, 'name' => $i->orderItem->product_name, 'size' => $i->orderItem->size, 'color' => $i->orderItem->color, 'qty' => $i->qty, 'restock' => (bool) $i->restock, 'unit_price' => (int) $i->orderItem->unit_price])->values(),
            'images' => $x->images->map(fn ($i) => storage_url($i->path))->values(), 'created_at' => $x->created_at,
        ];
    }

    public function returnAction(Request $r, int $id, string $action)
    {
        $x = ReturnRequest::with('order')->findOrFail($id);
        $staff = $r->user('staff');
        $need = ['approve' => 'return:process', 'reject' => 'return:process', 'receive' => 'return:restock', 'refund' => 'return:process'][$action] ?? abort(404);
        if (! $staff->hasPermission($need)) {
            throw ApiException::forbidden();
        }
        match ($action) {
            'approve' => $this->returns->approve($x, $staff->id, $r->input('note')),
            'reject' => $this->returns->reject($x, $staff->id, $r->input('note')),
            'receive' => $this->returns->receive($x, $staff->id, (array) $r->input('restock', [])),
            'refund' => $this->returns->refund($x, $staff->id, $r->filled('amount') ? (int) $r->input('amount') : null),
        };

        return ['data' => $this->returnRow($x->refresh()->load(['order', 'items.orderItem', 'images']))];
    }
}

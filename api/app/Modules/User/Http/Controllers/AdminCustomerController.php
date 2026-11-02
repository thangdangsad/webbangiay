<?php

namespace App\Modules\User\Http\Controllers;

use App\Modules\Auth\Services\TokenService;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\OrderPresenter;
use App\Modules\User\Models\User;
use Illuminate\Http\Request;

class AdminCustomerController
{
    public function index(Request $r)
    {
        $q = User::query()->withCount(['orders' => fn ($o) => $o->where('status', '!=', 'cancelled')])
            ->withSum(['orders as total_spent' => fn ($o) => $o->where('status', 'completed')], 'grand_total')->orderByDesc('id');
        if ($s = trim((string) $r->query('q'))) {
            $q->where(fn ($w) => $w->where('full_name', 'like', "%$s%")->orWhere('email', 'like', "%$s%")->orWhere('phone', 'like', "%$s%"));
        }
        if ($r->filled('status')) {
            $q->where('status', $r->query('status'));
        }
        $page = $q->paginate(min(100, (int) $r->query('limit', 20)));

        return ['data' => $page->getCollection()->map(fn ($u) => [
            'id' => $u->id, 'full_name' => $u->full_name, 'email' => $u->email, 'phone' => $u->phone, 'status' => $u->status,
            'orders_count' => $u->orders_count, 'total_spent' => (int) $u->total_spent, 'created_at' => $u->created_at,
        ])->values(), 'meta' => ['page' => $page->currentPage(), 'limit' => $page->perPage(), 'total' => $page->total()]];
    }

    public function show(int $id, OrderPresenter $p)
    {
        $u = User::with('addresses')->findOrFail($id);
        $orders = Order::with('items')->where('user_id', $id)->orderByDesc('placed_at')->limit(50)->get();

        return ['data' => [
            'id' => $u->id, 'full_name' => $u->full_name, 'email' => $u->email, 'phone' => $u->phone, 'gender' => $u->gender, 'birthday' => $u->birthday?->toDateString(),
            'status' => $u->status, 'email_verified' => (bool) $u->email_verified_at, 'created_at' => $u->created_at,
            'addresses' => $u->addresses,
            'stats' => ['orders' => $orders->where('status', '!=', 'cancelled')->count(), 'total_spent' => (int) $orders->where('status', 'completed')->sum('grand_total'), 'cancelled' => $orders->where('status', 'cancelled')->count()],
            'orders' => $orders->map(fn ($o) => $p->summary($o))->values(),
        ]];
    }

    public function status(Request $r, int $id)
    {
        $v = $r->validate(['status' => 'required|in:active,blocked']);
        $u = User::findOrFail($id);
        $u->update($v);
        if ($v['status'] === 'blocked') {
            TokenService::for('customer')->revokeAll($u->id);
        }

        return ['data' => ['id' => $u->id, 'status' => $u->status]];
    }
}

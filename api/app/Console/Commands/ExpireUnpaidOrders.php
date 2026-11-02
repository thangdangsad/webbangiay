<?php

namespace App\Console\Commands;

use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\OrderStateMachine;
use App\Modules\Payment\Models\Payment;
use App\Modules\Payment\Services\MomoService;
use Illuminate\Console\Command;

class ExpireUnpaidOrders extends Command
{
    protected $signature = 'orders:expire-unpaid';

    protected $description = 'Tự hủy đơn MoMo chưa thanh toán quá hạn (query MoMo trước khi hủy) và trả hàng giữ';

    public function handle(MomoService $momo, OrderStateMachine $machine): int
    {
        $n = 0;
        Payment::where('method', 'momo')->where('status', 'pending')->where('expires_at', '<', now())->with('order')->each(function ($p) use ($momo, $machine, &$n) {
            $order = $p->order;
            if (! $order || $order->status !== 'pending') {
                return;
            }
            if ($momo->query($order)['paid']) {
                return; // đã thanh toán: IPN/query đã chốt đơn
            }
            $p->update(['status' => 'failed']);
            $machine->transition($order->refresh(), 'cancelled', 'system', null, 'Quá hạn thanh toán MoMo');
            $n++;
        });
        $this->info("Đã hủy $n đơn quá hạn.");

        return self::SUCCESS;
    }
}

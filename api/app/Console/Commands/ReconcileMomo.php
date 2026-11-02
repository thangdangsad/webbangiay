<?php

namespace App\Console\Commands;

use App\Modules\Notification\Notifier;
use App\Modules\Payment\Models\Payment;
use App\Modules\Payment\Services\MomoService;
use Illuminate\Console\Command;

class ReconcileMomo extends Command
{
    protected $signature = 'payments:reconcile {--days=1}';

    protected $description = 'Đối soát giao dịch MoMo của N ngày gần nhất với MoMo (query), cảnh báo qua email khi lệch';

    public function handle(MomoService $momo): int
    {
        $diffs = [];
        Payment::where('method', 'momo')->whereNotNull('momo_order_id')->where('created_at', '>=', now()->subDays((int) $this->option('days')))
            ->whereIn('status', ['paid', 'pending', 'failed'])->with('order')->each(function (Payment $p) use ($momo, &$diffs) {
                if (! $p->order) {
                    return;
                }
                $remotePaid = $momo->query($p->order)['paid'];
                $p->refresh();
                $localPaid = $p->status === 'paid';
                if ($remotePaid !== $localPaid) {
                    $diffs[] = "{$p->order->code} ({$p->momo_order_id}): hệ thống=".($localPaid ? 'đã thu' : 'chưa thu').', MoMo='.($remotePaid ? 'đã thu' : 'chưa thu');
                }
            });
        if ($diffs) {
            Notifier::staff(['admin', 'manager'], 'Cảnh báo đối soát MoMo lệch', implode("\n", $diffs));
            $this->warn(count($diffs).' giao dịch lệch.');
        } else {
            $this->info('Đối soát khớp.');
        }

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SummarizeSales extends Command
{
    protected $signature = 'reports:summarize {--days=3 : số ngày gần nhất (không tính hôm nay) cần tính lại} {--all : tính lại toàn bộ lịch sử}';

    protected $description = 'Tổng hợp doanh thu theo ngày vào bảng daily_sales_summary';

    public function handle(): int
    {
        $tz = 'Asia/Ho_Chi_Minh';
        $first = DB::table('orders')->min('placed_at');
        $from = $this->option('all') && $first ? Carbon::parse($first)->setTimezone($tz)->startOfDay() : now($tz)->subDays((int) $this->option('days'))->startOfDay();
        $rows = DB::table('orders')->where('placed_at', '>=', $from->copy()->utc())
            ->selectRaw("DATE(CONVERT_TZ(placed_at,'+00:00','+07:00')) d, SUM(status != 'cancelled') orders, SUM(CASE WHEN status != 'cancelled' THEN grand_total ELSE 0 END) revenue, SUM(status = 'cancelled') cancelled")
            ->groupBy('d')->get();
        foreach ($rows as $r) {
            DB::table('daily_sales_summary')->updateOrInsert(['date' => $r->d], ['orders' => (int) $r->orders, 'revenue' => (int) $r->revenue, 'cancelled' => (int) $r->cancelled, 'updated_at' => now(), 'created_at' => now()]);
        }
        $this->info(count($rows).' ngày đã tổng hợp.');

        return self::SUCCESS;
    }
}

<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('orders:expire-unpaid')->everyMinute()->withoutOverlapping();
Schedule::command('files:cleanup-tmp')->daily();
Schedule::command('inventory:low-stock-alert')->dailyAt('08:00');
Schedule::command('payments:reconcile')->dailyAt('01:30');
Schedule::command('reports:summarize')->dailyAt('00:10');

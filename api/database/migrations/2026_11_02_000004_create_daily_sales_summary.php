<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_sales_summary', function (Blueprint $t) {
            $t->date('date')->primary();   // theo giờ Asia/Ho_Chi_Minh
            $t->unsignedInteger('orders')->default(0);
            $t->unsignedBigInteger('revenue')->default(0);
            $t->unsignedInteger('cancelled')->default(0);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_sales_summary');
    }
};

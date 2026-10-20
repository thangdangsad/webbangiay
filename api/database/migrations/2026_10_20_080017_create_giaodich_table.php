<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('giaodich', function (Blueprint $table) {
            $table->id('giaodich_id');
            $table->foreignId('khachhang_id')->nullable()
                ->constrained('customer', 'customer_id')
                ->nullOnDelete();
            // Not a strict FK: legacy code sometimes inserts literal 0 here as a
            // "no single product" sentinel for aggregate VNPAY/PayPal transactions.
            $table->unsignedBigInteger('sanpham_id')->nullable();
            $table->integer('soluong')->default(0);
            $table->string('magiaodich');
            $table->date('ngayThangNam')->nullable();
            $table->timestamps();

            $table->index('sanpham_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('giaodich');
    }
};

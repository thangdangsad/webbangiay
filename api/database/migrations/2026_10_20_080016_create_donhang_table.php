<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donhang', function (Blueprint $table) {
            $table->id('order_id');
            $table->foreignId('product_id')->nullable()
                ->constrained('product', 'product_id')
                ->nullOnDelete();
            $table->foreignId('customer_id')->nullable()
                ->constrained('customer', 'customer_id')
                ->nullOnDelete();
            $table->integer('soluong')->default(1);
            $table->decimal('tongDoanhThu', 12, 0)->default(0);
            $table->string('mahang');
            $table->date('ngayDatHang')->nullable();
            // order status: seen set to '1' on successful VNPAY/PayPal insert in legacy code
            $table->unsignedTinyInteger('trangThai')->nullable();
            // payment method code, duplicated here from `customer.payments` at order time
            $table->unsignedTinyInteger('payments')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donhang');
    }
};

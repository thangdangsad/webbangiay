<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `customer` = shipping/billing info snapshot captured per order at checkout time.
     * Distinct from `quanli_user`, which holds the customer's login account.
     */
    public function up(): void
    {
        Schema::create('customer', function (Blueprint $table) {
            $table->id('customer_id');
            $table->string('customer_name');
            $table->string('user_code')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_address')->nullable();
            // payment method code: 0 = COD, 1 = MoMo, 2 = MoMo ATM, 3 = PayPal
            $table->unsignedTinyInteger('payments')->nullable();
            $table->text('customer_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer');
    }
};

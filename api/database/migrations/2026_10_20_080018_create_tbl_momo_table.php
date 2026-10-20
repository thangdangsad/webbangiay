<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_momo', function (Blueprint $table) {
            $table->id('id_momo');
            $table->string('partner_Code')->nullable();
            $table->string('order_Id')->nullable();
            $table->decimal('amount', 12, 0)->default(0);
            $table->string('order_Info')->nullable();
            $table->string('order_Type')->nullable();
            $table->string('trans_Id')->nullable();
            $table->string('pay_type')->nullable();
            $table->string('code_cart')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_momo');
    }
};

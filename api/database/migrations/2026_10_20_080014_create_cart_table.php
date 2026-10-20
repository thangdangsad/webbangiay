<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart', function (Blueprint $table) {
            $table->id('cart_id');
            $table->foreignId('product_id')->nullable()
                ->constrained('product', 'product_id')
                ->cascadeOnDelete();
            $table->string('product_name');
            $table->decimal('product_price', 12, 0)->default(0);
            $table->string('product_image')->nullable();
            $table->integer('product_quantity')->default(1);

            // NEW columns (not present in legacy schema): fix the global shared-cart bug
            // by scoping each cart row to a logged-in customer or, if the visitor isn't
            // logged in, to a guest token (cookie/localStorage-issued identifier).
            $table->foreignId('customer_id')->nullable()
                ->constrained('quanli_user', 'user_id')
                ->nullOnDelete();
            $table->string('guest_token')->nullable()->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart');
    }
};

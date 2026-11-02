<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $t->string('coupon_code', 40)->nullable();
            $t->timestamps();
        });

        Schema::create('cart_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $t->foreignId('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $t->unsignedInteger('qty');
            $t->timestamps();
            $t->unique(['cart_id', 'variant_id']);
        });

        Schema::create('coupons', function (Blueprint $t) {
            $t->id();
            $t->string('code', 40)->unique();
            $t->string('type', 10);   // percent|fixed|free_ship
            $t->unsignedBigInteger('value')->default(0);
            $t->unsignedBigInteger('min_order')->default(0);
            $t->unsignedBigInteger('max_discount')->nullable();
            $t->unsignedInteger('usage_limit')->nullable();
            $t->unsignedInteger('per_user_limit')->nullable();
            $t->timestamp('starts_at')->nullable();
            $t->timestamp('ends_at')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('promotions', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('discount_type', 10);   // percent|fixed
            $t->unsignedBigInteger('value');
            $t->timestamp('starts_at')->nullable();
            $t->timestamp('ends_at')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('promotion_targets', function (Blueprint $t) {
            $t->id();
            $t->foreignId('promotion_id')->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('product_id')->nullable();
            $t->unsignedBigInteger('category_id')->nullable();
            $t->unsignedBigInteger('brand_id')->nullable();
        });

        Schema::create('banners', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('image_path')->nullable();
            $t->string('mobile_image_path')->nullable();
            $t->string('link')->nullable();
            $t->string('position', 20)->default('hero');
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamp('starts_at')->nullable();
            $t->timestamp('ends_at')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('orders', function (Blueprint $t) {
            $t->id();
            $t->string('code', 20)->unique();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('customer_name', 120);
            $t->string('phone', 15)->index();
            $t->string('email', 190)->nullable();
            $t->json('ship_address');
            $t->string('status', 15)->default('pending');
            $t->string('payment_status', 15)->default('unpaid');
            $t->string('payment_method', 10)->default('cod');
            $t->unsignedBigInteger('subtotal');
            $t->unsignedBigInteger('discount_total')->default(0);
            $t->unsignedBigInteger('shipping_fee')->default(0);
            $t->unsignedBigInteger('grand_total');
            $t->string('coupon_code', 40)->nullable();
            $t->text('note')->nullable();
            $t->text('internal_note')->nullable();
            $t->string('idempotency_key', 80)->nullable()->unique();
            $t->timestamp('placed_at')->nullable();
            $t->timestamp('cancelled_at')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
            $t->index(['user_id', 'placed_at']);
            $t->index(['status', 'placed_at']);
        });

        Schema::create('order_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('variant_id')->nullable();
            $t->unsignedBigInteger('product_id')->nullable();
            $t->string('product_name');
            $t->string('sku', 64);
            $t->string('color', 60);
            $t->string('size', 20);
            $t->string('image_path')->nullable();
            $t->unsignedBigInteger('unit_price');
            $t->unsignedInteger('qty');
            $t->unsignedBigInteger('line_total');
        });

        Schema::create('order_status_history', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->string('from_status', 15)->nullable();
            $t->string('to_status', 15);
            $t->string('actor_type', 10);   // customer|staff|system
            $t->unsignedBigInteger('actor_id')->nullable();
            $t->text('note')->nullable();
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('coupon_redemptions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('amount');
            $t->timestamps();
        });

        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->string('method', 10);
            $t->unsignedBigInteger('amount');
            $t->string('status', 10)->default('pending');
            $t->string('provider', 10)->nullable();
            $t->string('momo_order_id', 40)->nullable()->index();
            $t->string('momo_request_id', 60)->nullable();
            $t->string('provider_txn_id', 40)->nullable()->unique();
            $t->integer('result_code')->nullable();
            $t->json('raw_payload')->nullable();
            $t->timestamp('expires_at')->nullable();
            $t->timestamp('paid_at')->nullable();
            $t->timestamps();
        });

        Schema::create('shipments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->string('carrier', 60)->nullable();
            $t->string('tracking_code', 60)->nullable();
            $t->unsignedBigInteger('fee')->default(0);
            $t->string('status', 15)->default('created');
            $t->timestamp('shipped_at')->nullable();
            $t->timestamp('delivered_at')->nullable();
            $t->timestamps();
        });

        Schema::create('shipping_rates', function (Blueprint $t) {
            $t->id();
            $t->string('zone', 80);
            $t->json('province_codes')->nullable();
            $t->unsignedInteger('min_weight')->default(0);
            $t->unsignedInteger('max_weight')->default(100000);
            $t->unsignedBigInteger('fee');
            $t->unsignedBigInteger('free_threshold')->nullable();
            $t->timestamps();
        });

        Schema::create('returns', function (Blueprint $t) {
            $t->id();
            $t->string('code', 20)->unique();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->text('reason');
            $t->string('status', 12)->default('requested');
            $t->unsignedBigInteger('refund_amount')->default(0);
            $t->unsignedBigInteger('staff_id')->nullable();
            $t->text('staff_note')->nullable();
            $t->timestamps();
        });

        Schema::create('return_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('return_id')->constrained('returns')->cascadeOnDelete();
            $t->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $t->unsignedInteger('qty');
            $t->boolean('restock')->default(true);
        });

        Schema::create('return_images', function (Blueprint $t) {
            $t->id();
            $t->foreignId('return_id')->constrained('returns')->cascadeOnDelete();
            $t->string('path');
        });

        Schema::create('product_reviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('order_item_id')->nullable();
            $t->unsignedTinyInteger('rating');
            $t->text('content')->nullable();
            $t->string('status', 10)->default('pending');
            $t->timestamps();
        });

        Schema::create('wishlists', function (Blueprint $t) {
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->timestamps();
            $t->primary(['user_id', 'product_id']);
        });

        Schema::create('cms_pages', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('title');
            $t->longText('content')->nullable();
            $t->boolean('is_published')->default(true);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['cms_pages', 'wishlists', 'product_reviews', 'return_images', 'return_items', 'returns', 'shipping_rates', 'shipments', 'payments', 'coupon_redemptions', 'order_status_history', 'order_items', 'orders', 'banners', 'promotion_targets', 'promotions', 'coupons', 'cart_items', 'carts'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};

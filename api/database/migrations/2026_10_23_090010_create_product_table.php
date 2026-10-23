<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * New shoe-level `product` table (fresh auto-increment `product_id`,
 * unrelated to the old `product.product_id` now renamed to
 * `product_variant.variant_id` — see the preceding migration). One row per
 * shoe style; `product_variant` holds one row per size.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product', function (Blueprint $table) {
            $table->id('product_id');
            $table->string('product_name');
            $table->string('product_image')->nullable();
            $table->decimal('product_price', 12, 0)->default(0);
            $table->decimal('product_discount', 12, 0)->nullable();
            $table->text('product_details')->nullable();
            $table->text('product_description')->nullable();
            // legacy typo preserved deliberately (see
            // 2026_10_20_080011_create_product_table.php, same convention).
            $table->unsignedTinyInteger('product_acitve')->default(0);
            $table->unsignedTinyInteger('product_hot')->default(0);
            $table->foreignId('category_id')->nullable()
                ->constrained('tbl_category', 'category_id')
                ->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('product_variant', function (Blueprint $table) {
            // Deleting a whole Product is a deliberate, explicit admin
            // action and cascades its variants away with it — distinct from
            // the "never hard-delete a single variant via the edit form"
            // rule enforced in Admin\ProductController::update(), which only
            // soft-deactivates (is_active = false) a variant removed from an
            // otherwise-kept product.
            $table->foreign('product_id')
                ->references('product_id')->on('product')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('product_variant', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
        });

        Schema::dropIfExists('product');
    }
};

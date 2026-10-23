<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Repoints `cart`, `donhang` and `giaodich` at `product_variant` following
 * the product/variant split. The old FKs from `cart` and `donhang` into the
 * original `product` table were already dropped in the rename migration
 * (required prep before that migration could rename
 * `product.product_id` -> `product_variant.variant_id` while those FKs
 * still referenced it by name).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Renamed via an explicit CHANGE COLUMN for the same reason as the
        // product_id -> variant_id rename in the preceding migration.
        DB::statement('ALTER TABLE cart CHANGE COLUMN product_id variant_id BIGINT UNSIGNED NULL');

        Schema::table('cart', function (Blueprint $table) {
            $table->foreign('variant_id')
                ->references('variant_id')->on('product_variant')
                ->cascadeOnDelete();

            // Size snapshot, like the existing product_name/product_price/
            // product_image snapshot columns: two sizes of the same shoe are
            // now two distinct product_variant rows, and today's cart UI has
            // no other way to show which size a cart line is.
            $table->string('size')->nullable()->after('variant_id');
        });

        Schema::table('donhang', function (Blueprint $table) {
            // Column name intentionally kept as `product_id` (not renamed);
            // it stores a `product_variant.variant_id` value post-split.
            $table->foreign('product_id')
                ->references('variant_id')->on('product_variant')
                ->nullOnDelete();
        });

        // `giaodich.sanpham_id` has no real FK constraint today (legacy
        // sometimes inserts literal 0 as a "no single product" sentinel for
        // aggregate VNPAY/PayPal transactions) and gets none added here; it
        // now refers to `product_variant.variant_id` conceptually, with no
        // schema change required.
    }

    public function down(): void
    {
        Schema::table('donhang', function (Blueprint $table) {
            $table->dropForeign('donhang_product_id_foreign');
        });

        Schema::table('cart', function (Blueprint $table) {
            $table->dropColumn('size');
            $table->dropForeign('cart_variant_id_foreign');
        });

        DB::statement('ALTER TABLE cart CHANGE COLUMN variant_id product_id BIGINT UNSIGNED NULL');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Splits the legacy one-row-per-size `product` table into a shoe-level
 * `product` table (next migration) and this renamed-in-place
 * `product_variant` table (one row per size). This is a rename, not a
 * copy: `variant_id` keeps the exact same values and auto-increment
 * counter as the old `product_id`, so every existing `cart.product_id`,
 * `donhang.product_id` and `giaodich.sanpham_id` value keeps pointing at
 * exactly the correct row with zero data migration on those tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Drop the only two FKs that reference `product.product_id` before
        // renaming that column below — MySQL/TiDB refuses to rename a
        // column while another table's FK still refers to it by name. The
        // next migration re-adds these, repointed at `product_variant`.
        Schema::table('cart', function (Blueprint $table) {
            $table->dropForeign('cart_product_id_foreign');
        });

        Schema::table('donhang', function (Blueprint $table) {
            $table->dropForeign('donhang_product_id_foreign');
        });

        Schema::rename('product', 'product_variant');

        // Renamed via an explicit CHANGE COLUMN (not Schema::renameColumn())
        // so AUTO_INCREMENT/PRIMARY KEY are preserved explicitly rather than
        // relying on native-rename-column version detection against TiDB's
        // non-standard MySQL version string.
        DB::statement('ALTER TABLE product_variant CHANGE COLUMN product_id variant_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');

        Schema::table('product_variant', function (Blueprint $table) {
            // Constraint name was generated from the original table name
            // ("product"); renaming the table above does not rename it.
            $table->dropForeign('product_category_id_foreign');

            // These columns moved up to the new shoe-level `product` table.
            $table->dropColumn([
                'product_name',
                'product_image',
                'product_price',
                'product_discount',
                'product_details',
                'product_description',
                'product_acitve',
                'product_hot',
                'category_id',
            ]);

            // Nullable for now: nothing has been backfilled yet. A later,
            // human-reviewed migration (out of scope here) makes this NOT
            // NULL once a dry-run backfill against real data is reviewed.
            // The FK to `product.product_id` is added in the next migration,
            // once that table exists.
            $table->unsignedBigInteger('product_id')->nullable()->after('variant_id');

            // Soft-deactivation flag: a variant with order history must
            // never be hard-deleted (see Admin\ProductController::update()).
            $table->boolean('is_active')->default(true)->after('product_sold');
        });
    }

    public function down(): void
    {
        Schema::table('product_variant', function (Blueprint $table) {
            $table->dropColumn(['product_id', 'is_active']);

            $table->string('product_name')->after('variant_id');
            $table->string('product_image')->nullable();
            $table->decimal('product_price', 12, 0)->default(0);
            $table->decimal('product_discount', 12, 0)->nullable();
            $table->text('product_details')->nullable();
            $table->text('product_description')->nullable();
            $table->unsignedTinyInteger('product_acitve')->default(0);
            $table->unsignedTinyInteger('product_hot')->default(0);
            $table->foreignId('category_id')->nullable()
                ->constrained('tbl_category', 'category_id')
                ->nullOnDelete();
        });

        DB::statement('ALTER TABLE product_variant CHANGE COLUMN variant_id product_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');

        Schema::rename('product_variant', 'product');

        Schema::table('cart', function (Blueprint $table) {
            $table->foreign('product_id')
                ->references('product_id')->on('product')
                ->cascadeOnDelete();
        });

        Schema::table('donhang', function (Blueprint $table) {
            $table->foreign('product_id')
                ->references('product_id')->on('product')
                ->nullOnDelete();
        });
    }
};

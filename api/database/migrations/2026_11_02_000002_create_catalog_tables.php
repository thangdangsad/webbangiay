<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $t) {
            $t->id();
            $t->string('name', 120);
            $t->string('slug')->unique();
            $t->string('logo_path')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $t->string('name', 120);
            $t->string('slug')->unique();
            $t->unsignedInteger('sort_order')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('colors', function (Blueprint $t) {
            $t->id();
            $t->string('name', 60);
            $t->string('hex', 9);
            $t->timestamps();
        });

        Schema::create('sizes', function (Blueprint $t) {
            $t->id();
            $t->string('system', 4)->default('EU');
            $t->string('label', 20);
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamps();
            $t->unique(['system', 'label']);
        });

        Schema::create('products', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $t->string('gender', 10)->default('unisex');
            $t->text('description')->nullable();
            $t->string('material')->nullable();
            $t->unsignedBigInteger('base_price')->default(0);
            $t->string('status', 10)->default('draft');
            $t->string('seo_title')->nullable();
            $t->string('seo_description')->nullable();
            $t->unsignedBigInteger('sold_count')->default(0);
            $t->softDeletes();
            $t->timestamps();
            $t->index(['status', 'category_id', 'brand_id']);
        });
        DB::statement('ALTER TABLE products ADD FULLTEXT ft_products_name (name) WITH PARSER ngram');

        Schema::create('product_variants', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->string('sku', 64)->unique();
            $t->foreignId('color_id')->constrained();
            $t->foreignId('size_id')->constrained();
            $t->unsignedBigInteger('price');
            $t->unsignedBigInteger('compare_at_price')->nullable();
            $t->string('barcode', 64)->nullable();
            $t->unsignedInteger('weight_g')->default(800);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->unique(['product_id', 'color_id', 'size_id']);
        });

        Schema::create('product_images', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->foreignId('color_id')->nullable()->constrained()->nullOnDelete();
            $t->string('path');
            $t->string('alt')->nullable();
            $t->unsignedInteger('sort_order')->default(0);
            $t->boolean('is_primary')->default(false);
            $t->unsignedInteger('width')->nullable();
            $t->unsignedInteger('height')->nullable();
            $t->unsignedInteger('size_bytes')->nullable();
            $t->timestamps();
        });

        Schema::create('inventory', function (Blueprint $t) {
            $t->foreignId('variant_id')->primary()->constrained('product_variants')->cascadeOnDelete();
            $t->integer('on_hand')->default(0);
            $t->integer('reserved')->default(0);
            $t->unsignedInteger('low_stock_threshold')->default(5);
            $t->unsignedInteger('version')->default(0);
            $t->timestamps();
        });
        DB::statement('ALTER TABLE inventory ADD CONSTRAINT chk_inventory CHECK (on_hand >= 0 AND reserved >= 0 AND reserved <= on_hand)');

        Schema::create('inventory_movements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $t->string('type', 10);   // receipt|sale|reserve|release|return|adjust
            $t->integer('qty_delta');
            $t->string('reason')->nullable();
            $t->string('ref_type', 30)->nullable();
            $t->unsignedBigInteger('ref_id')->nullable();
            $t->unsignedBigInteger('staff_id')->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['variant_id', 'created_at']);
        });

        Schema::create('stock_receipts', function (Blueprint $t) {
            $t->id();
            $t->string('code', 20)->unique();
            $t->string('supplier')->nullable();
            $t->text('note')->nullable();
            $t->unsignedBigInteger('staff_id')->nullable();
            $t->timestamps();
        });

        Schema::create('stock_receipt_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('stock_receipt_id')->constrained()->cascadeOnDelete();
            $t->foreignId('variant_id')->constrained('product_variants');
            $t->unsignedInteger('qty');
            $t->unsignedBigInteger('unit_cost')->default(0);
        });
    }

    public function down(): void
    {
        foreach (['stock_receipt_items', 'stock_receipts', 'inventory_movements', 'inventory', 'product_images', 'product_variants', 'products', 'sizes', 'colors', 'categories', 'brands'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};

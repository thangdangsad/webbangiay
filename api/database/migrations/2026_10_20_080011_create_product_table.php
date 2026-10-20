<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product', function (Blueprint $table) {
            $table->id('product_id');
            $table->string('product_name');
            $table->string('size')->nullable();
            $table->string('product_image')->nullable();
            $table->integer('product_quantity')->default(0);
            $table->decimal('product_price', 12, 0)->default(0);
            $table->decimal('product_discount', 12, 0)->nullable();
            $table->text('product_details')->nullable();
            $table->text('product_description')->nullable();
            // legacy flags: product_hot drives the "best seller" sidebar query
            // (WHERE product_hot='0') on the legacy homepage; product_acitve
            // ("active", sic — typo preserved from the legacy column name) is
            // an enable/disable flag; product_sold tracks a running sold count.
            $table->unsignedTinyInteger('product_acitve')->default(0);
            $table->unsignedTinyInteger('product_hot')->default(0);
            $table->unsignedInteger('product_sold')->default(0);
            $table->foreignId('category_id')->nullable()
                ->constrained('tbl_category', 'category_id')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product');
    }
};

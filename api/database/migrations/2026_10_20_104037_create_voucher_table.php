<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('voucher', function (Blueprint $table) {
            $table->id('voucher_id');
            $table->string('code')->unique();
            $table->decimal('discount', 10, 2);
            $table->unsignedInteger('min_quantity')->default(0);
            $table->boolean('used')->default(false);
            // not a strict FK in the legacy schema (no constraint defined there either)
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voucher');
    }
};

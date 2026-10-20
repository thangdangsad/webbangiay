<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `quanli_user` = customer login accounts (the "customer" Sanctum guard).
     * Not to be confused with `customer`, which stores per-order shipping/billing info.
     */
    public function up(): void
    {
        Schema::create('quanli_user', function (Blueprint $table) {
            $table->id('user_id');
            $table->string('user_name');
            $table->string('user_code')->nullable();
            $table->string('user_email')->unique();
            $table->string('user_phone')->nullable();
            $table->string('user_address')->nullable();
            // legacy plaintext password; new accounts use bcrypt, lazily migrated on login
            $table->string('user_password');
            $table->unsignedTinyInteger('role')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quanli_user');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `quanly_taikhoan` = admin/staff login accounts (the "admin" Sanctum guard).
     * role: 1 = admin, 2 = staff.
     */
    public function up(): void
    {
        Schema::create('quanly_taikhoan', function (Blueprint $table) {
            $table->id('user_id');
            $table->string('user_name');
            $table->string('user_code')->nullable();
            $table->string('user_email')->unique();
            $table->string('user_phone')->nullable();
            $table->string('user_address')->nullable();
            // legacy unsalted md5() password; new accounts use bcrypt, lazily migrated on login
            $table->string('user_password');
            $table->unsignedTinyInteger('role');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quanly_taikhoan');
    }
};

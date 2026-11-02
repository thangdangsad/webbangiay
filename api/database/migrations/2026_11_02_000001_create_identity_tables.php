<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('email', 190)->nullable()->unique();
            $t->string('phone', 15)->nullable()->unique();
            $t->string('password_hash')->nullable();
            $t->string('full_name', 120);
            $t->string('gender', 10)->nullable();
            $t->date('birthday')->nullable();
            $t->string('status', 10)->default('active');
            $t->timestamp('email_verified_at')->nullable();
            $t->timestamps();
        });

        Schema::create('roles', function (Blueprint $t) {
            $t->id();
            $t->string('code', 40)->unique();
            $t->string('name', 80);
            $t->timestamps();
        });

        Schema::create('permissions', function (Blueprint $t) {
            $t->id();
            $t->string('code', 80)->unique();   // resource:action
            $t->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $t) {
            $t->foreignId('role_id')->constrained()->cascadeOnDelete();
            $t->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $t->primary(['role_id', 'permission_id']);
        });

        Schema::create('staff_users', function (Blueprint $t) {
            $t->id();
            $t->string('email', 190)->unique();
            $t->string('password_hash');
            $t->string('full_name', 120);
            $t->foreignId('role_id')->constrained();
            $t->string('status', 10)->default('active');
            $t->timestamp('last_login_at')->nullable();
            $t->string('two_factor_secret', 64)->nullable();
            $t->timestamps();
        });

        Schema::create('addresses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('receiver_name', 120);
            $t->string('phone', 15);
            $t->string('province_code', 10);
            $t->string('district_code', 10);
            $t->string('ward_code', 10);
            $t->string('street', 255);
            $t->boolean('is_default')->default(false);
            $t->timestamps();
        });

        Schema::create('settings', function (Blueprint $t) {
            $t->string('key', 80)->primary();
            $t->json('value')->nullable();
            $t->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('staff_id')->nullable()->index();
            $t->string('action', 40);
            $t->string('entity', 60);
            $t->unsignedBigInteger('entity_id')->nullable();
            $t->json('before')->nullable();
            $t->json('after')->nullable();
            $t->string('ip', 45)->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['entity', 'entity_id']);
        });
    }

    public function down(): void
    {
        foreach (['audit_logs', 'settings', 'addresses', 'staff_users', 'role_permissions', 'permissions', 'roles', 'users'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};

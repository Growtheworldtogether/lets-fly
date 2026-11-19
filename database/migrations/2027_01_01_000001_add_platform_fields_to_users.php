<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 10)->default('member')->after('password'); // member | admin
            $table->string('member_number', 24)->nullable()->unique()->after('role');
            $table->string('phone', 30)->nullable();
            $table->string('status', 20)->default('active');
            $table->string('signup_country', 2)->nullable();
            $table->string('last_country', 2)->nullable();
            $table->unsignedSmallInteger('failed_logins')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('intro_seen_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'member_number', 'phone', 'status', 'signup_country', 'last_country', 'failed_logins', 'locked_until', 'last_login_at', 'intro_seen_at']);
        });
    }
};

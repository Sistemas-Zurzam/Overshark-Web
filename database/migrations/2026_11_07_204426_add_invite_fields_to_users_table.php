<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('invited_at')->nullable()->after('status');
            $table->timestamp('last_login_at')->nullable()->after('invited_at');
            $table->boolean('all_brands_access')->default(false)->after('last_login_at');
        });

        Schema::create('brand_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->unique(['user_id', 'brand_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_user');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['invited_at', 'last_login_at', 'all_brands_access']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banners_portada', function (Blueprint $table) {
            $table->foreignId('brand_id')->nullable()->after('id')->constrained('brands')->nullOnDelete();
            $table->date('starts_at')->nullable()->after('status');
            $table->date('ends_at')->nullable()->after('starts_at');
            $table->unsignedInteger('sort_order')->default(0)->after('ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('banners_portada', function (Blueprint $table) {
            $table->dropConstrainedForeignId('brand_id');
            $table->dropColumn(['starts_at', 'ends_at', 'sort_order']);
        });
    }
};

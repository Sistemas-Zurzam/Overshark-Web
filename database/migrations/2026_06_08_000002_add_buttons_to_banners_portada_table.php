<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banners_portada', function (Blueprint $table) {
            $table->json('buttons')->nullable()->after('modo');
            $table->string('buttons_position')->default('center-left')->after('buttons');
        });
    }

    public function down(): void
    {
        Schema::table('banners_portada', function (Blueprint $table) {
            $table->dropColumn(['buttons', 'buttons_position']);
        });
    }
};

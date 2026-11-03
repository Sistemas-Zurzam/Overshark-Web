<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brand_settings', function (Blueprint $table) {
            $table->string('whatsapp_number', 20)->nullable();
            $table->string('whatsapp_message', 300)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('brand_settings', function (Blueprint $table) {
            $table->dropColumn(['whatsapp_number', 'whatsapp_message']);
        });
    }
};

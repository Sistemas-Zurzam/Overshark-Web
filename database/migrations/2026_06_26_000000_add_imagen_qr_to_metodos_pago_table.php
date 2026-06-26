<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('metodos_pago', function (Blueprint $table) {
            $table->string('imagen_qr')->nullable()->after('imagen');
        });
    }

    public function down(): void
    {
        Schema::table('metodos_pago', function (Blueprint $table) {
            $table->dropColumn('imagen_qr');
        });
    }
};

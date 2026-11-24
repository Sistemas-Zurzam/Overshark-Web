<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Imagen del combo para celular (opcional): si existe, la tienda la usa por debajo de 768 px en lugar de la de escritorio. */
    public function up(): void
    {
        Schema::table('combos', function (Blueprint $table): void {
            $table->string('imagen_movil')->nullable()->after('imagen');
        });
    }

    public function down(): void
    {
        Schema::table('combos', function (Blueprint $table): void {
            $table->dropColumn('imagen_movil');
        });
    }
};

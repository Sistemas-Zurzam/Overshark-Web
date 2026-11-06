<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Separo de los pedidos contraentrega (S/ 15 a domicilio, S/ 30 en agencia); el saldo se paga al recibir.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('separo', 12, 2)->nullable()->after('precio_total');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('separo');
        });
    }
};

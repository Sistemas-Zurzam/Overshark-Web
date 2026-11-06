<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Pago en el checkout: cuánto se pagó en línea (el total, o el separo en contraentrega) y la referencia del
// procesador (id del cargo de Culqi).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('pago_monto', 12, 2)->nullable()->after('separo');
            $table->string('pago_referencia')->nullable()->after('pago_monto');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['pago_monto', 'pago_referencia']);
        });
    }
};

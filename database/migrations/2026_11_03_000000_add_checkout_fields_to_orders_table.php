<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Datos del checkout web (web/checkout/*): contacto, entrega, comprobante y pago. checkout_token evita
 * crear dos pedidos si la clienta envía el formulario dos veces.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('email')->nullable()->after('cliente_cel');
            $table->decimal('subtotal', 12, 2)->nullable()->after('precio_total');
            $table->decimal('envio', 12, 2)->nullable()->after('subtotal');
            $table->string('entrega', 20)->nullable()->after('tipo_envio_id');
            $table->string('agencia')->nullable()->after('entrega');
            $table->string('referencia')->nullable()->after('direccion');
            $table->string('tipo_comprobante', 20)->nullable()->after('referencia');
            $table->string('ruc', 11)->nullable()->after('tipo_comprobante');
            $table->string('razon_social')->nullable()->after('ruc');
            $table->string('metodo_pago_nombre')->nullable()->after('metodo_pago_id');
            $table->string('comprobante_pago')->nullable()->after('metodo_pago_nombre');
            $table->string('checkout_token', 64)->nullable()->unique()->after('cuenta_cliente');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['checkout_token']);
            $table->dropColumn(['email', 'subtotal', 'envio', 'entrega', 'agencia', 'referencia', 'tipo_comprobante', 'ruc', 'razon_social', 'metodo_pago_nombre', 'comprobante_pago', 'checkout_token']);
        });
    }
};

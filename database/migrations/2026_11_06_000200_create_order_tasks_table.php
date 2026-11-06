<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Lo que pasa después de pagar un pedido (App\Services\Orders\OrderFulfillment): una fila por tarea
// —nota de venta en Zazu, pago al ERP, boleta o factura, correo— con su estado, intentos y resultado.
// Y el QR de Yape del pedido mientras se espera el pago.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('task', 40);
            // pendiente · listo · omitido (falta integrar) · fallido (agotó los reintentos)
            $table->string('status', 20)->default('pendiente');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->text('message')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['order_id', 'task']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->text('pago_qr')->nullable()->after('pago_referencia');
            $table->timestamp('pago_qr_expira')->nullable()->after('pago_qr');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['pago_qr', 'pago_qr_expira']);
        });
        Schema::dropIfExists('order_tasks');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Alertas de fraude de Mercado Pago (webhook stop_delivery_op_wh): el pedido queda marcado para no despacharlo hasta
 * que el equipo la revise.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('fraud_alert_at')->nullable()->after('payment_gateway');
            $table->string('fraud_alert_detail', 600)->nullable()->after('fraud_alert_at');
            $table->timestamp('fraud_alert_reviewed_at')->nullable()->after('fraud_alert_detail');
        });
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn(['fraud_alert_at', 'fraud_alert_detail', 'fraud_alert_reviewed_at']));
    }
};

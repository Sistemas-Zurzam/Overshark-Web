<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * El panel ya no confirma pagos a mano. Una contraentrega sin cobro en línea (recojo en almacén) que ya se envió al CRM
 * se quedó en «Pendiente de confirmación» aunque no había nada que confirmar: pasa a «Contraentrega» (En preparación).
 * Solo se tocan los pedidos que ya arrancaron su proceso (tienen tareas); el resto sigue como estaba.
 */
return new class extends Migration
{
    public function up(): void
    {
        $pendingId = DB::table('status_orders')->where('name', 'Pendiente de confirmación')->value('id');

        if (! $pendingId) {
            return;
        }

        $targetId = DB::table('status_orders')->where('name', 'Contraentrega')->value('id')
            ?? DB::table('status_orders')->insertGetId(['name' => 'Contraentrega', 'created_at' => now(), 'updated_at' => now()]);

        DB::table('orders')
            ->where('status_order_id', $pendingId)
            ->where('metodo_pago_nombre', 'like', 'Pago contraentrega%')
            ->where(fn ($query) => $query->whereNull('separo')->orWhere('separo', 0))
            ->whereExists(fn ($query) => $query->selectRaw('1')->from('order_tasks')->whereColumn('order_tasks.order_id', 'orders.id'))
            ->update(['status_order_id' => $targetId, 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Sin marcha atrás: el estado anterior era el mismo pedido sin confirmar.
    }
};

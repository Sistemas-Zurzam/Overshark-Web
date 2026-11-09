<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Productos ocultos de la tienda (Admin → Productos → «Ocultar de la tienda»). Zazu no conoce este campo, así que
 * la sincronización lo respeta; se marca en todas las variantes (colores y tallas) del producto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->boolean('oculto')->default(false)->index()->after('stock');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropIndex(['oculto']);
            $table->dropColumn('oculto');
        });
    }
};

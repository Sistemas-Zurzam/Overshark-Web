<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Venta del pedido en CRM-LIVEX-V2 (App\Services\Crm\CrmSales): la nota de venta NV-00014 que asigna el CRM y el
// id de la venta allá. agencia_shalom_id es el id de Shalom de la agencia elegida, para que el CRM la reconozca.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('agencia_shalom_id', 40)->nullable()->after('agencia');
            $table->string('nota_venta', 40)->nullable()->index()->after('number_zazu');
            $table->unsignedBigInteger('crm_sale_id')->nullable()->after('nota_venta');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['nota_venta']);
            $table->dropColumn(['agencia_shalom_id', 'nota_venta', 'crm_sale_id']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->unsignedBigInteger('odoo_product_id')->nullable()->unique()->after('id');
            $table->string('default_code')->nullable()->index()->after('categoria_id');
            $table->json('variant_values')->nullable()->after('name');
            $table->string('color')->nullable()->after('variant_values');
            $table->string('talla')->nullable()->after('color');
            $table->decimal('standard_price', 12, 2)->nullable()->after('price');
            $table->decimal('qty_available', 12, 2)->default(0)->after('standard_price');
            $table->timestamp('odoo_synced_at')->nullable()->after('imagen');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropUnique(['odoo_product_id']);
            $table->dropIndex(['default_code']);
            $table->dropColumn([
                'odoo_product_id',
                'default_code',
                'variant_values',
                'color',
                'talla',
                'standard_price',
                'qty_available',
                'odoo_synced_at',
            ]);
        });
    }
};

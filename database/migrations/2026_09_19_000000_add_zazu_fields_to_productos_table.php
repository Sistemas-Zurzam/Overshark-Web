<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->string('zazu_source_key', 120)->nullable()->unique()->after('id');
            $table->unsignedBigInteger('zazu_product_id')->nullable()->index()->after('zazu_source_key');
            $table->unsignedBigInteger('zazu_variant_id')->nullable()->index()->after('zazu_product_id');
            $table->unsignedBigInteger('zazu_company_id')->nullable()->index()->after('zazu_variant_id');
            $table->string('empresa_nombre')->nullable()->after('zazu_company_id');
            $table->timestamp('zazu_synced_at')->nullable()->after('empresa_nombre');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropUnique(['zazu_source_key']);
            $table->dropIndex(['zazu_product_id']);
            $table->dropIndex(['zazu_variant_id']);
            $table->dropIndex(['zazu_company_id']);
            $table->dropColumn([
                'zazu_source_key',
                'zazu_product_id',
                'zazu_variant_id',
                'zazu_company_id',
                'empresa_nombre',
                'zazu_synced_at',
            ]);
        });
    }
};

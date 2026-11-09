<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Se quita el módulo Bodegas del panel: era solo una etiqueta de organización que ni la tienda, ni el checkout ni la
 * sincronización de Zazu usaban (el stock lo maneja Zazu). Se elimina la columna productos.bodega_id y la tabla.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('productos', 'bodega_id')) {
            Schema::table('productos', function (Blueprint $table) {
                $table->dropConstrainedForeignId('bodega_id');
            });
        }

        Schema::dropIfExists('bodegas');
    }

    public function down(): void
    {
        Schema::create('bodegas', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('subtitle')->nullable();
            $table->string('address')->nullable();
            $table->string('responsable')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->foreignId('bodega_id')->nullable()->after('categoria_id')->constrained('bodegas')->nullOnDelete();
        });
    }
};

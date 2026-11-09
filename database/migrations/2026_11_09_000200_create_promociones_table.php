<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Promociones por horas ("ofertas relámpago"): un descuento sobre prendas concretas, una marca o una categoría que solo
 * vale entre dos instantes. Se guardan en UTC; el panel las muestra y recibe en hora de Lima. No tocan productos.price
 * (lo pisa cada sincronización de Zazu): el precio vigente se calcula sobre el precio de Zazu (App\Support\Promotions).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promociones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('label', 40)->nullable();
            $table->string('scope_type', 20);
            $table->json('scope_values');
            $table->string('discount_type', 20);
            $table->decimal('discount_value', 10, 2);
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['active', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promociones');
    }
};

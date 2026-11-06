<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Reglas de "Te puede interesar" (App\Support\CartSuggestions): al agregar un producto —o una marca— al carrito,
// qué prendas se le sugieren. Se administran en el panel (Sugerencias).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_suggestions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // producto: se activa con una prenda concreta · marca: con cualquier prenda de esa marca
            $table->string('trigger_type', 20);
            $table->string('trigger_value');
            $table->json('products');
            $table->string('title')->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_suggestions');
    }
};

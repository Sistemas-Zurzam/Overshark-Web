<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('imagen')->nullable();
            $table->timestamps();
        });

        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_id')->nullable()->constrained('categorias')->nullOnDelete();
            $table->string('name');
            $table->unsignedInteger('stock')->default(0);
            $table->decimal('price', 12, 2);
            $table->string('imagen')->nullable();
            $table->timestamps();
        });

        Schema::create('variantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->string('talla')->nullable();
            $table->string('color')->nullable();
            $table->boolean('prime')->default(false);
            $table->unsignedInteger('stock')->default(0);
            $table->string('imagen')->nullable();
            $table->timestamps();
        });

        Schema::create('tipos_registro', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('tipos_documento', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->timestamps();
        });

        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipo_registro_id')->nullable()->constrained('tipos_registro')->nullOnDelete();
            $table->foreignId('tipo_documento_id')->nullable()->constrained('tipos_documento')->nullOnDelete();
            $table->string('name');
            $table->string('apellidos')->nullable();
            $table->string('documento_identidad')->nullable()->index();
            $table->string('cel', 30)->nullable();
            $table->string('email')->nullable()->index();
            $table->string('etiqueta')->nullable();
            $table->timestamps();
        });

        Schema::create('metodos_pago', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('imagen')->nullable();
            $table->timestamps();
        });

        Schema::create('combos', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('imagen')->nullable();
            $table->timestamps();
        });

        Schema::create('combo_producto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('combo_id')->constrained('combos')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->unsignedInteger('cantidad')->default(1);
            $table->unique(['combo_id', 'producto_id']);
        });

        Schema::create('redes_sociales', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('icono')->nullable();
            $table->string('url')->nullable();
            $table->timestamps();
        });

        Schema::create('departamentos', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('provincias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('departamento_id')->constrained('departamentos')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('distritos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provincia_id')->constrained('provincias')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('status_orders', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('tipos_envio', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number_zazu')->unique();
            $table->json('productos_json')->nullable();
            $table->decimal('precio_total', 12, 2);
            $table->foreignId('metodo_pago_id')->nullable()->constrained('metodos_pago')->nullOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->string('cliente_name');
            $table->string('cliente_cel', 30)->nullable();
            $table->string('cliente_doc_identidad')->nullable();
            $table->foreignId('tipo_documento_id')->nullable()->constrained('tipos_documento')->nullOnDelete();
            $table->foreignId('status_order_id')->constrained('status_orders')->restrictOnDelete();
            $table->foreignId('tipo_envio_id')->nullable()->constrained('tipos_envio')->nullOnDelete();
            $table->foreignId('tipo_registro_id')->nullable()->constrained('tipos_registro')->nullOnDelete();
            $table->foreignId('distrito_id')->nullable()->constrained('distritos')->nullOnDelete();
            $table->string('direccion')->nullable();
            $table->string('cuenta_cliente')->nullable();
            $table->timestamps();
        });

        Schema::create('banners_portada', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('status')->default(true);
            $table->unsignedInteger('time')->nullable()->comment('Duracion en segundos');
            $table->string('modo')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners_portada');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('tipos_envio');
        Schema::dropIfExists('status_orders');
        Schema::dropIfExists('distritos');
        Schema::dropIfExists('provincias');
        Schema::dropIfExists('departamentos');
        Schema::dropIfExists('redes_sociales');
        Schema::dropIfExists('combo_producto');
        Schema::dropIfExists('combos');
        Schema::dropIfExists('metodos_pago');
        Schema::dropIfExists('clientes');
        Schema::dropIfExists('tipos_documento');
        Schema::dropIfExists('tipos_registro');
        Schema::dropIfExists('variantes');
        Schema::dropIfExists('productos');
        Schema::dropIfExists('categorias');
    }
};

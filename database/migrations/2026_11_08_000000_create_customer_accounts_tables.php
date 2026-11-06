<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Cuentas de compradores (guard "customer"), separadas de `users`, que es solo del administrador (JWT).
// Los datos comerciales siguen en `clientes`; el pedido se liga a la cuenta con orders.customer_account_id
// (nulo en pedidos de invitados y en los anteriores a las cuentas). Solo agrega: no toca datos existentes.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('apellidos')->nullable();
            $table->string('email')->unique();
            $table->string('phone', 30)->nullable();
            // Cambio de correo pendiente de confirmar desde el enlace enviado al correo nuevo.
            $table->string('pending_email')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        // Tokens de recuperación propios: password_reset_tokens se indexa por correo y la comparte el admin.
        Schema::create('customer_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_account_id')->constrained('customer_accounts')->cascadeOnDelete();
            $table->string('alias', 40);
            $table->string('recipient');
            $table->string('phone', 30);
            $table->foreignId('distrito_id')->constrained('distritos')->restrictOnDelete();
            $table->string('address');
            $table->string('reference')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('customer_account_id')->nullable()->after('cliente_id')->constrained('customer_accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_account_id');
        });
        Schema::dropIfExists('customer_addresses');
        Schema::dropIfExists('customer_password_reset_tokens');
        Schema::dropIfExists('customer_accounts');
    }
};

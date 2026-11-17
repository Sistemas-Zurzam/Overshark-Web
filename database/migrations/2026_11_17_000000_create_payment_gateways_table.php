<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->unique();
            $table->boolean('enabled')->default(false);
            $table->string('environment')->default('test');
            $table->text('credentials')->nullable();
            $table->timestamps();
        });

        foreach (['culqi', 'izipay', 'mercadopago'] as $provider) {
            $enabled = match ($provider) {
                'izipay' => filled(config('services.izipay.shop_id'))
                    && filled(config('services.izipay.password'))
                    && filled(config('services.izipay.public_key'))
                    && filled(config('services.izipay.hmac_key')),
                default => false,
            };
            DB::table('payment_gateways')->insert([
                'provider' => $provider,
                'enabled' => $enabled,
                'environment' => app()->environment('production') ? 'production' : 'test',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_gateway')->nullable()->after('pago_referencia');
        });
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn('payment_gateway'));
        Schema::dropIfExists('payment_gateways');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier', function (Blueprint $table) {
            $table->id();
            $table->string('ter_id')->unique();
            $table->string('direccion', 500)->nullable();
            $table->string('zona')->nullable();
            $table->string('provincia')->nullable();
            $table->string('departamento')->nullable();
            $table->string('lugar_over')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index(['departamento', 'provincia'], 'courier_department_province_idx');
            $table->index(['departamento', 'lugar_over'], 'courier_department_lugar_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier');
    }
};

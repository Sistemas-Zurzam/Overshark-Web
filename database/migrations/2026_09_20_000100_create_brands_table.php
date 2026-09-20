<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->string('slug', 140)->unique();
            $table->string('primary_color', 7)->default('#0078D7');
            $table->string('secondary_color', 7)->default('#111111');
            $table->string('accent_color', 7)->default('#E8F4FF');
            $table->string('background_color', 7)->default('#F1F2F4');
            $table->string('text_color', 7)->default('#111111');
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });

        $now = now();
        DB::table('brands')->insert([
            [
                'name' => 'OVERSHARK',
                'slug' => 'overshark',
                'primary_color' => '#0078D7',
                'secondary_color' => '#111111',
                'accent_color' => '#E8F4FF',
                'background_color' => '#F1F2F4',
                'text_color' => '#111111',
                'is_active' => true,
                'sort_order' => 10,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'BRAVOS',
                'slug' => 'bravos',
                'primary_color' => '#D97706',
                'secondary_color' => '#172033',
                'accent_color' => '#FFF4DE',
                'background_color' => '#F5F1EB',
                'text_color' => '#172033',
                'is_active' => true,
                'sort_order' => 20,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'OVERSHARK GIRLS',
                'slug' => 'overshark-girls',
                'primary_color' => '#C64F83',
                'secondary_color' => '#2B1723',
                'accent_color' => '#FDEAF2',
                'background_color' => '#FBF4F7',
                'text_color' => '#2B1723',
                'is_active' => true,
                'sort_order' => 30,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        DB::table('productos')
            ->where(function ($query) {
                $query->whereNull('marca')->orWhereRaw("TRIM(marca) = ''");
            })
            ->where('empresa_nombre', 'like', '%OVERSHARK%')
            ->update(['marca' => 'OVERSHARK']);
    }

    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};

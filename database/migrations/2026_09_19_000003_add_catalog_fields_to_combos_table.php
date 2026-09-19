<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('combos', function (Blueprint $table) {
            $table->string('source_key', 180)->nullable()->unique()->after('id');
            $table->string('brand', 80)->nullable()->after('name');
            $table->string('modality', 80)->nullable()->after('brand');
            $table->decimal('price', 12, 2)->nullable()->after('modality');
            $table->json('items')->nullable()->after('price');
            $table->string('selection_mode', 20)->default('fixed')->after('items');
            $table->unsignedInteger('selection_limit')->nullable()->after('selection_mode');
            $table->text('notes')->nullable()->after('selection_limit');
        });
    }

    public function down(): void
    {
        Schema::table('combos', function (Blueprint $table) {
            $table->dropUnique(['source_key']);
            $table->dropColumn([
                'source_key',
                'brand',
                'modality',
                'price',
                'items',
                'selection_mode',
                'selection_limit',
                'notes',
            ]);
        });
    }
};

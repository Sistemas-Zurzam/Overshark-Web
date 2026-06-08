<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->text('descripcion')->nullable()->after('imagen');
            $table->text('composicion')->nullable()->after('descripcion');
            $table->text('cuidados')->nullable()->after('composicion');
            $table->string('material')->nullable()->after('cuidados');
            $table->string('fit')->nullable()->after('material');
            $table->string('sensacion')->nullable()->after('fit');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn([
                'descripcion',
                'composicion',
                'cuidados',
                'material',
                'fit',
                'sensacion',
            ]);
        });
    }
};

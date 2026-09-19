<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departamentos', function (Blueprint $table) {
            $table->string('codigo', 10)->nullable()->index()->after('id');
        });

        Schema::table('provincias', function (Blueprint $table) {
            $table->string('codigo', 10)->nullable()->index()->after('departamento_id');
        });

        Schema::table('distritos', function (Blueprint $table) {
            $table->string('codigo_reniec', 12)->nullable()->index()->after('provincia_id');
            $table->string('codigo_inei', 12)->nullable()->index()->after('codigo_reniec');
            $table->unsignedBigInteger('entity_id')->nullable()->index()->after('codigo_inei');
        });
    }

    public function down(): void
    {
        Schema::table('distritos', function (Blueprint $table) {
            $table->dropIndex(['codigo_reniec']);
            $table->dropIndex(['codigo_inei']);
            $table->dropIndex(['entity_id']);
            $table->dropColumn(['codigo_reniec', 'codigo_inei', 'entity_id']);
        });

        Schema::table('provincias', function (Blueprint $table) {
            $table->dropIndex(['codigo']);
            $table->dropColumn('codigo');
        });

        Schema::table('departamentos', function (Blueprint $table) {
            $table->dropIndex(['codigo']);
            $table->dropColumn('codigo');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->unsignedBigInteger('odoo_template_id')->nullable()->index()->after('odoo_product_id');
        });

        Schema::create('producto_color_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('odoo_template_id')->nullable()->index();
            $table->string('product_name')->index();
            $table->string('color')->index();
            $table->json('images')->nullable();
            $table->timestamps();
            $table->unique(['odoo_template_id', 'color']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_color_images');

        Schema::table('productos', function (Blueprint $table) {
            $table->dropIndex(['odoo_template_id']);
            $table->dropColumn('odoo_template_id');
        });
    }
};

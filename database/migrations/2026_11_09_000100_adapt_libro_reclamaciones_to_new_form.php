<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nuevo formulario del Libro de Reclamaciones (Figma "Libro de Reclamaciones"): una sola hoja con teléfono y pedido
 * opcionales, sin comprobante/canal/fecha de compra, con contacto del representante, documento de sustento y número
 * de hoja. Los campos que el formulario ya no pide pasan a ser opcionales para no perder los registros anteriores.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('libro_reclamaciones', function (Blueprint $table) {
            $table->string('phone', 30)->nullable()->change();
            $table->string('receipt_type', 30)->nullable()->change();
            $table->string('order_number', 80)->nullable()->change();
            $table->date('purchase_date')->nullable()->change();
            $table->string('purchase_channel', 80)->nullable()->change();
            $table->string('order_product')->nullable()->change();
            $table->string('claim_product')->nullable()->change();
        });

        Schema::table('libro_reclamaciones', function (Blueprint $table) {
            $table->string('sheet_number', 20)->nullable()->unique()->after('id');
            $table->string('brand', 40)->default('Overshark Hombre')->after('sheet_number');
            $table->string('guardian_contact')->nullable()->after('guardian_document_number');
            $table->string('supporting_document')->nullable()->after('claim_description');
        });
    }

    public function down(): void
    {
        Schema::table('libro_reclamaciones', function (Blueprint $table) {
            $table->dropUnique(['sheet_number']);
            $table->dropColumn(['sheet_number', 'brand', 'guardian_contact', 'supporting_document']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('libro_reclamaciones', function (Blueprint $table) {
            $table->id();
            $table->string('consumer_name');
            $table->string('document_type', 20);
            $table->string('document_number', 30);
            $table->string('address');
            $table->string('email');
            $table->string('phone', 30);
            $table->boolean('is_minor')->default(false);
            $table->string('guardian_name')->nullable();
            $table->string('guardian_document_type', 20)->nullable();
            $table->string('guardian_document_number', 30)->nullable();
            $table->string('receipt_type', 30);
            $table->string('order_number', 80);
            $table->date('purchase_date');
            $table->string('purchase_channel', 80);
            $table->decimal('claimed_amount', 10, 2)->nullable();
            $table->string('order_product');
            $table->text('order_description')->nullable();
            $table->string('claim_type', 20);
            $table->text('expected_solution');
            $table->string('claim_product');
            $table->text('claim_description');
            $table->string('status', 30)->default('pendiente');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('libro_reclamaciones');
    }
};

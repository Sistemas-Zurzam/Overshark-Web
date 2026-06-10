<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LibroReclamacion extends Model
{
    protected $table = 'libro_reclamaciones';

    protected $fillable = [
        'consumer_name',
        'document_type',
        'document_number',
        'address',
        'email',
        'phone',
        'is_minor',
        'guardian_name',
        'guardian_document_type',
        'guardian_document_number',
        'receipt_type',
        'order_number',
        'purchase_date',
        'purchase_channel',
        'claimed_amount',
        'order_product',
        'order_description',
        'claim_type',
        'expected_solution',
        'claim_product',
        'claim_description',
        'status',
    ];

    protected $casts = [
        'is_minor' => 'boolean',
        'purchase_date' => 'date',
        'claimed_amount' => 'decimal:2',
    ];
}

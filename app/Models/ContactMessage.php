<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    public const SUBJECTS = [
        'pedido' => 'Consulta sobre un pedido',
        'producto' => 'Consulta sobre un producto o talla',
        'cambios' => 'Cambios y devoluciones',
        'mayorista' => 'Ventas por mayor',
        'otro' => 'Otro',
    ];

    protected $fillable = ['name', 'email', 'phone', 'subject', 'order_number', 'message', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function subjectLabel(): string
    {
        return self::SUBJECTS[$this->subject] ?? $this->subject;
    }
}

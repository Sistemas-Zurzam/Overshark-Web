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

    public const PREFERENCES = [
        'correo' => 'Correo',
        'whatsapp' => 'WhatsApp',
        'telefono' => 'Teléfono',
    ];

    protected $fillable = ['name', 'email', 'phone', 'prefers_contact', 'subject', 'order_number', 'message', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function subjectLabel(): string
    {
        return self::SUBJECTS[$this->subject] ?? $this->subject;
    }

    public function preferenceLabel(): string
    {
        return self::PREFERENCES[$this->prefers_contact] ?? self::PREFERENCES['correo'];
    }

    public function maskedEmail(): string
    {
        [$local, $domain] = array_pad(explode('@', (string) $this->email, 2), 2, '');

        return mb_substr($local, 0, 3).'•••@'.$domain;
    }

    /** Enlace wa.me para responder por WhatsApp, o null si no dejó celular. Mismo criterio que BrandSetting::whatsappUrl(). */
    public function whatsappUrl(): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->phone);

        if (strlen($digits) === 9 && str_starts_with($digits, '9')) {
            $digits = '51'.$digits;
        }

        return strlen($digits) < 10 ? null : 'https://wa.me/'.$digits;
    }
}

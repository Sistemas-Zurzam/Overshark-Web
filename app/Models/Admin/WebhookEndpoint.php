<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** URL externa que recibe avisos de pedidos firmados con su secreto (X-Overshark-Signature). */
class WebhookEndpoint extends Model
{
    public const EVENTS = [
        'order.confirmed' => 'Pedido confirmado (pagado o contraentrega aceptado)',
        'order.shipped' => 'Pedido enviado',
        'order.delivered' => 'Pedido entregado',
        'order.cancelled' => 'Pedido anulado',
    ];

    protected $fillable = ['name', 'url', 'events', 'secret', 'enabled', 'last_status', 'last_error', 'last_attempt_at'];

    protected $hidden = ['secret'];

    protected function casts(): array
    {
        return ['events' => 'array', 'secret' => 'encrypted', 'enabled' => 'boolean', 'last_attempt_at' => 'datetime'];
    }

    public static function newSecret(): string
    {
        return 'whsec_'.Str::random(40);
    }

    public function listensTo(string $event): bool
    {
        return $this->enabled && in_array($event, $this->events ?? [], true);
    }
}

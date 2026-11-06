<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Una tarea posterior al pago de un pedido (ver App\Services\Orders\OrderFulfillment). */
class OrderTask extends Model
{
    public const PENDING = 'pendiente';

    public const DONE = 'listo';

    public const SKIPPED = 'omitido';

    public const FAILED = 'fallido';

    protected $fillable = ['order_id', 'task', 'status', 'attempts', 'message', 'data', 'completed_at'];

    protected function casts(): array
    {
        return ['data' => 'array', 'completed_at' => 'datetime'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}

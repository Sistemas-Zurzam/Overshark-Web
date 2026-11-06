<?php

namespace App\Models;

use App\Models\Admin\Distrito;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Libreta de direcciones de una cuenta. El pedido guarda su propia copia, así que borrar una no cambia el historial. */
class CustomerAddress extends Model
{
    protected $fillable = ['alias', 'recipient', 'phone', 'distrito_id', 'address', 'reference', 'lat', 'lng', 'is_default'];

    protected function casts(): array
    {
        return ['is_default' => 'boolean', 'lat' => 'float', 'lng' => 'float'];
    }

    public function distrito(): BelongsTo
    {
        return $this->belongsTo(Distrito::class);
    }

    /** "Distrito, Provincia, Departamento" para mostrar la dirección completa. */
    public function placeLabel(): string
    {
        $distrito = $this->distrito;

        return collect([$distrito?->name, $distrito?->provincia?->name, $distrito?->provincia?->departamento?->name])->filter()->implode(', ');
    }
}

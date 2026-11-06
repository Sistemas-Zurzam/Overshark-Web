<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CartSuggestion extends Model
{
    public const TRIGGER_PRODUCT = 'producto';

    public const TRIGGER_BRAND = 'marca';

    protected $fillable = ['name', 'trigger_type', 'trigger_value', 'products', 'title', 'active', 'sort', 'starts_at', 'ends_at'];

    protected function casts(): array
    {
        return [
            'products' => 'array',
            'active' => 'boolean',
            'starts_at' => 'date',
            'ends_at' => 'date',
        ];
    }

    /** Reglas activas y dentro de su rango de fechas (si lo tienen). */
    public function scopeRunning(Builder $query): Builder
    {
        return $query->where('active', true)
            ->where(fn (Builder $inner) => $inner->whereNull('starts_at')->orWhereDate('starts_at', '<=', today()))
            ->where(fn (Builder $inner) => $inner->whereNull('ends_at')->orWhereDate('ends_at', '>=', today()));
    }
}

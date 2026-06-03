<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Variante extends Model
{
    protected $fillable = ['producto_id', 'talla', 'color', 'prime', 'stock', 'imagen'];

    protected function casts(): array
    {
        return ['prime' => 'boolean'];
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}

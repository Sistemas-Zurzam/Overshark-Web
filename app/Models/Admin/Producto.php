<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $fillable = ['categoria_id', 'name', 'stock', 'price', 'imagen'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2'];
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function variantes()
    {
        return $this->hasMany(Variante::class);
    }

    public function combos()
    {
        return $this->belongsToMany(Combo::class, 'combo_producto')->withPivot('cantidad');
    }
}

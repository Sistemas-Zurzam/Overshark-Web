<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class Combo extends Model
{
    protected $fillable = ['name', 'imagen', 'status', 'url'];

    protected function casts(): array
    {
        return ['status' => 'boolean'];
    }

    public function imageUrl(): ?string
    {
        return $this->imagen ? '/storage/'.ltrim($this->imagen, '/') : null;
    }

    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'combo_producto')->withPivot('cantidad');
    }
}

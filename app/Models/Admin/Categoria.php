<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $fillable = ['name', 'imagen'];

    public function productos()
    {
        return $this->hasMany(Producto::class);
    }
}

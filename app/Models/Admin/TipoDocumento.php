<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class TipoDocumento extends Model
{
    protected $table = 'tipos_documento';

    protected $fillable = ['name', 'code'];

    public function clientes()
    {
        return $this->hasMany(Cliente::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}

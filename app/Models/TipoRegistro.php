<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoRegistro extends Model
{
    protected $table = 'tipos_registro';

    protected $fillable = ['name'];

    public function clientes()
    {
        return $this->hasMany(Cliente::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}

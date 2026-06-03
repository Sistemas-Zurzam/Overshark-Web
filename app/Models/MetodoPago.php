<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetodoPago extends Model
{
    protected $table = 'metodos_pago';

    protected $fillable = ['name', 'imagen'];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}

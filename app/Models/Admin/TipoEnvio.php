<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class TipoEnvio extends Model
{
    protected $table = 'tipos_envio';

    protected $fillable = ['name'];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}

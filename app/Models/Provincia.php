<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Provincia extends Model
{
    protected $fillable = ['departamento_id', 'name'];

    public function departamento()
    {
        return $this->belongsTo(Departamento::class);
    }

    public function distritos()
    {
        return $this->hasMany(Distrito::class);
    }
}

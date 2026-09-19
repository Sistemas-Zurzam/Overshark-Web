<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class Departamento extends Model
{
    protected $fillable = ['codigo', 'name'];

    public function provincias()
    {
        return $this->hasMany(Provincia::class);
    }
}

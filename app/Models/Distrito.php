<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Distrito extends Model
{
    protected $fillable = ['provincia_id', 'name'];

    public function provincia()
    {
        return $this->belongsTo(Provincia::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}

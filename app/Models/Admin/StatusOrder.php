<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class StatusOrder extends Model
{
    protected $fillable = ['name'];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}

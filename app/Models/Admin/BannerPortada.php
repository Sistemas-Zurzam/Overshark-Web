<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class BannerPortada extends Model
{
    protected $table = 'banners_portada';

    protected $fillable = ['name', 'status', 'time', 'modo'];

    protected function casts(): array
    {
        return ['status' => 'boolean'];
    }
}

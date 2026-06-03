<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class RedSocial extends Model
{
    protected $table = 'redes_sociales';

    protected $fillable = ['name', 'icono', 'url'];
}

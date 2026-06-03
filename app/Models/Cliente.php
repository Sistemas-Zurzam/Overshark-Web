<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $fillable = [
        'tipo_registro_id', 'tipo_documento_id', 'name', 'apellidos',
        'documento_identidad', 'cel', 'email', 'etiqueta',
    ];

    public function tipoRegistro()
    {
        return $this->belongsTo(TipoRegistro::class);
    }

    public function tipoDocumento()
    {
        return $this->belongsTo(TipoDocumento::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}

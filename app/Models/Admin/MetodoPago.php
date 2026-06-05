<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class MetodoPago extends Model
{
    protected $table = 'metodos_pago';

    protected $fillable = ['name', 'imagen', 'status'];

    protected function casts(): array
    {
        return ['status' => 'boolean'];
    }

    public function imageUrl(): ?string
    {
        return $this->imagen ? '/storage/'.ltrim($this->imagen, '/') : null;
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}

<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class Bodega extends Model
{
    protected $fillable = ['name', 'subtitle', 'address', 'responsable', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function productos()
    {
        return $this->hasMany(Producto::class);
    }

    /** Productos distintos (agrupados por nombre + empresa) asignados a esta bodega. */
    public function productCount(): int
    {
        return Producto::query()->where('bodega_id', $this->id)
            ->select('name', 'zazu_company_id')
            ->groupBy('name', 'zazu_company_id')
            ->get()
            ->count();
    }

    public function unitCount(): int
    {
        return (int) Producto::query()->where('bodega_id', $this->id)->sum('stock');
    }
}

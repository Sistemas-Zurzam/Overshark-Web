<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $fillable = [
        'categoria_id',
        'odoo_product_id',
        'odoo_template_id',
        'default_code',
        'name',
        'variant_values',
        'color',
        'talla',
        'stock',
        'price',
        'standard_price',
        'qty_available',
        'imagen',
        'descripcion',
        'composicion',
        'cuidados',
        'material',
        'fit',
        'sensacion',
        'guia_tallas_imagen',
        'odoo_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'variant_values' => 'array',
            'odoo_product_id' => 'integer',
            'odoo_template_id' => 'integer',
            'price' => 'decimal:2',
            'standard_price' => 'decimal:2',
            'qty_available' => 'decimal:2',
            'odoo_synced_at' => 'datetime',
        ];
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function variantes()
    {
        return $this->hasMany(Variante::class);
    }

    public function combos()
    {
        return $this->belongsToMany(Combo::class, 'combo_producto')->withPivot('cantidad');
    }

    public function imageUrl(): ?string
    {
        return $this->imagen ? '/storage/'.ltrim($this->imagen, '/') : null;
    }

    public function sizeGuideImageUrl(): ?string
    {
        return $this->guia_tallas_imagen ? '/storage/'.ltrim($this->guia_tallas_imagen, '/') : null;
    }
}

<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class ProductoColorImage extends Model
{
    protected $fillable = ['odoo_template_id', 'product_name', 'color', 'images'];

    protected function casts(): array
    {
        return [
            'images' => 'array',
        ];
    }

    public function imageUrls(): array
    {
        return collect($this->images ?? [])
            ->map(fn (string $path) => '/storage/'.ltrim($path, '/'))
            ->all();
    }
}

<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class BannerPortada extends Model
{
    protected $table = 'banners_portada';

    protected $fillable = ['name', 'image_path', 'status', 'time', 'modo', 'buttons', 'buttons_position'];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'buttons' => 'array',
        ];
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? '/storage/'.ltrim($this->image_path, '/') : null;
    }
}

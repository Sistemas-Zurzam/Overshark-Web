<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

/** Conexión de salida configurada en Admin → Integraciones (crm, zazu, pixels). Las credenciales van cifradas. */
class IntegrationSetting extends Model
{
    public const PROVIDERS = ['crm', 'zazu', 'pixels'];

    protected $fillable = ['provider', 'enabled', 'settings'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean', 'settings' => 'encrypted:array'];
    }

    public static function for(string $provider): self
    {
        return self::query()->firstOrNew(['provider' => $provider], ['enabled' => false, 'settings' => []]);
    }

    public function value(string $key, mixed $fallback = null): mixed
    {
        $value = data_get($this->settings ?? [], $key);

        return filled($value) ? $value : $fallback;
    }
}

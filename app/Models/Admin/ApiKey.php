<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Clave de la API de Overshark (/api/v1) para un sistema externo. Se muestra completa una sola vez al crearla;
 * aquí solo queda su hash SHA-256 y el prefijo para reconocerla en el panel.
 */
class ApiKey extends Model
{
    public const ABILITIES = [
        'catalog.read' => 'Leer catálogo y stock',
        'orders.read' => 'Leer pedidos',
        'orders.write' => 'Actualizar el estado de pedidos',
    ];

    protected $fillable = ['name', 'token_prefix', 'token_hash', 'abilities', 'last_used_at', 'revoked_at'];

    protected function casts(): array
    {
        return ['abilities' => 'array', 'last_used_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    /** Crea la clave y devuelve [modelo, token en claro] (el token no se puede recuperar después). */
    public static function issue(string $name, array $abilities): array
    {
        $plain = 'osk_'.Str::random(40);
        $key = self::query()->create([
            'name' => $name,
            'token_prefix' => substr($plain, 0, 12),
            'token_hash' => hash('sha256', $plain),
            'abilities' => array_values(array_intersect(array_keys(self::ABILITIES), $abilities)),
        ]);

        return [$key, $plain];
    }

    public static function findActive(string $plain): ?self
    {
        return $plain === '' ? null : self::query()->active()->where('token_hash', hash('sha256', $plain))->first();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('revoked_at');
    }

    public function allows(string ...$abilities): bool
    {
        return array_intersect($abilities, $this->abilities ?? []) !== [];
    }
}

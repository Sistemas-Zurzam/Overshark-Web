<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class PaymentGateway extends Model
{
    public const PROVIDERS = ['culqi', 'izipay', 'mercadopago'];

    protected $fillable = ['provider', 'enabled', 'environment', 'credentials'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean', 'credentials' => 'encrypted:array'];
    }

    public function label(): string
    {
        return ['culqi' => 'Culqi', 'izipay' => 'Izipay', 'mercadopago' => 'Mercado Pago'][$this->provider] ?? $this->provider;
    }

    public function credential(string $key, mixed $fallback = null): mixed
    {
        return data_get($this->credentials ?? [], $key) ?: $fallback;
    }

    public function isConfigured(): bool
    {
        return match ($this->provider) {
            'culqi' => filled($this->credential('public_key')) && filled($this->credential('secret_key')),
            'mercadopago' => filled($this->credential('access_token')) && filled($this->credential('webhook_secret')),
            'izipay' => collect(['shop_id', 'password', 'hmac_key', 'public_key'])
                ->every(fn ($key) => filled($this->credential($key))),
            default => false,
        };
    }

    public function isAvailable(): bool
    {
        // Culqi permanece oculto hasta que se integre su formulario de pago en esta versión del checkout.
        return $this->enabled && $this->isConfigured() && in_array($this->provider, ['izipay', 'mercadopago'], true);
    }
}

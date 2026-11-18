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

    /** Credenciales que usa el flujo de pago de cada pasarela; el resto (p. ej. Client ID) son opcionales. */
    public function requiredCredentials(): array
    {
        return match ($this->provider) {
            'culqi' => ['public_key' => 'Llave pública', 'secret_key' => 'Llave secreta'],
            // Basta el Access Token: el pago se verifica en la API. Con Public Key se cobra con el formulario de tarjeta
            // en la tienda (Card Payment Brick); sin ella, con Checkout Pro. El secreto del webhook es recomendado
            // (valida la firma de los avisos), pero sin él los pagos igual se confirman.
            'mercadopago' => ['access_token' => 'Access Token'],
            'izipay' => ['shop_id' => 'ID de tienda', 'password' => 'Contraseña API REST', 'public_key' => 'Llave pública', 'hmac_key' => 'Clave HMAC-SHA256'],
            default => [],
        };
    }

    /** @return list<string> etiquetas de las credenciales requeridas que faltan */
    public function missingCredentials(): array
    {
        return collect($this->requiredCredentials())
            // Izipay se configuraba en el .env antes de este panel: esas llaves siguen sirviendo si no hay otras guardadas.
            ->reject(fn ($label, $key) => filled($this->credential($key, $this->provider === 'izipay' ? config('services.izipay.'.$key) : null)))
            ->values()
            ->all();
    }

    public function isConfigured(): bool
    {
        return $this->requiredCredentials() !== [] && $this->missingCredentials() === [];
    }

    public function isAvailable(): bool
    {
        // Culqi permanece oculto hasta que se integre su formulario de pago en esta versión del checkout.
        return $this->enabled && $this->isConfigured() && in_array($this->provider, ['izipay', 'mercadopago'], true);
    }
}

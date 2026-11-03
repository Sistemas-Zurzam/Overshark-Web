<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class BrandSetting extends Model
{
    private const CACHE_KEY = 'brand_setting.current';

    protected $fillable = [
        'logo_path',
        'icon_path',
        'whatsapp_number',
        'whatsapp_message',
    ];

    public const WHATSAPP_DEFAULT_MESSAGE = 'Hola Overshark, quiero información sobre sus productos.';

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    public static function current(): self
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => static::query()->firstOrCreate([]));
    }

    public function logoUrl(): ?string
    {
        return $this->publicUrl($this->logo_path);
    }

    public function iconUrl(): ?string
    {
        return $this->publicUrl($this->icon_path);
    }

    /**
     * Enlace wa.me del botón flotante de WhatsApp, o null si no hay número configurado.
     * Un celular peruano de 9 dígitos (9XXXXXXXX) se completa con el código de país 51.
     */
    public function whatsappUrl(): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->whatsapp_number);

        if (strlen($digits) === 9 && str_starts_with($digits, '9')) {
            $digits = '51'.$digits;
        }

        if (strlen($digits) < 10) {
            return null;
        }

        return 'https://wa.me/'.$digits.'?text='.rawurlencode($this->whatsapp_message ?: self::WHATSAPP_DEFAULT_MESSAGE);
    }

    /** Número para mostrar («+51 957 249 042»); null si no hay un número válido. */
    public function whatsappDisplay(): ?string
    {
        if (! $this->whatsappUrl()) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', (string) $this->whatsapp_number);

        if (strlen($digits) === 9) {
            $digits = '51'.$digits;
        }

        return str_starts_with($digits, '51') && strlen($digits) === 11
            ? '+51 '.implode(' ', str_split(substr($digits, 2), 3))
            : '+'.$digits;
    }

    private function publicUrl(?string $path): ?string
    {
        return $path ? '/storage/'.ltrim($path, '/') : null;
    }
}

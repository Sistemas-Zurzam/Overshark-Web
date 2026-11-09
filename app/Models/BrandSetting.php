<?php

namespace App\Models;

use App\Support\SiteContent;

/**
 * Marca del sitio: ícono de la pestaña, logo de la pantalla de elección y WhatsApp. Ya no es una tabla propia: los
 * valores se editan en Admin → Contenido de la tienda (WhatsApp) y Admin → Banners (ícono y logo) y viven en
 * App\Support\SiteContent. La clase conserva los métodos que usan las vistas.
 */
class BrandSetting
{
    public const WHATSAPP_DEFAULT_MESSAGE = 'Hola Overshark, quiero información sobre sus productos.';

    public static function current(): self
    {
        return new self;
    }

    public function logoUrl(): ?string
    {
        return SiteContent::imageUrl('site_logo');
    }

    public function iconUrl(): ?string
    {
        return SiteContent::imageUrl('site_icon');
    }

    private function number(): string
    {
        return (string) SiteContent::get('contacto.whatsapp', '');
    }

    /**
     * Enlace wa.me del botón flotante de WhatsApp, o null si no hay número configurado.
     * Un celular peruano de 9 dígitos (9XXXXXXXX) se completa con el código de país 51.
     */
    public function whatsappUrl(): ?string
    {
        $digits = preg_replace('/\D+/', '', $this->number());

        if (strlen($digits) === 9 && str_starts_with($digits, '9')) {
            $digits = '51'.$digits;
        }

        if (strlen($digits) < 10) {
            return null;
        }

        return 'https://wa.me/'.$digits.'?text='.rawurlencode(SiteContent::get('contacto.whatsapp_message') ?: self::WHATSAPP_DEFAULT_MESSAGE);
    }

    /** Número para mostrar («+51 957 249 042»); null si no hay un número válido. */
    public function whatsappDisplay(): ?string
    {
        if (! $this->whatsappUrl()) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $this->number());

        if (strlen($digits) === 9) {
            $digits = '51'.$digits;
        }

        return str_starts_with($digits, '51') && strlen($digits) === 11
            ? '+51 '.implode(' ', str_split(substr($digits, 2), 3))
            : '+'.$digits;
    }
}

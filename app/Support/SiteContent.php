<?php

namespace App\Support;

use App\Models\PageContent;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

/**
 * Contenido editable de la tienda (Admin → Contenido de la tienda): barra superior y promos, precios de envío y
 * separo, contacto y preguntas frecuentes, datos del proveedor, textos de las portadas y de los documentos legales.
 *
 * Cada valor tiene un texto por defecto (el que antes estaba escrito en las vistas); lo que se edita en el panel va
 * encima y un campo vacío vuelve al valor por defecto. Los textos pueden llevar marcas que se reemplazan con los
 * valores vigentes, para que un precio se escriba en un solo sitio:
 *   {lima} envío a domicilio · {agencia} envío en agencia · {desde} el envío más barato · {separo} separo en agencia
 *   {separo_lima} separo a domicilio · {combo} precio del combo (p. ej. «3 × S/ 50»)
 */
class SiteContent
{
    public const SLUG = 'tienda';

    public const FAQ_SLOTS = 10;

    public const LEGAL_SECTIONS = 8;

    public static function defaults(): array
    {
        return [
            'promos' => [
                'combo' => '3 × S/ 50',
                'anuncio_pago' => 'Pago contraentrega',
                'wafflera_title' => '9 prendas × S/ 99',
                'wafflera_text' => 'Elige color y talla de cada prenda. Llegan juntas en un solo envío.',
            ],
            'envio' => [
                'home' => (float) config('services.storefront.shipping_home', 15),
                'agency' => (float) config('services.storefront.shipping_agency', 14),
                'deposit_home' => (float) config('services.storefront.cod_deposit_home', 15),
                'deposit_agency' => (float) config('services.storefront.cod_deposit_agency', 30),
            ],
            'proveedor' => ['razon_social' => '', 'ruc' => '', 'domicilio' => '', 'canal' => ''],
            'contacto' => [
                'email' => 'ventas@overshark.com',
                'horario' => '9am a 6pm L-V',
                'whatsapp' => '',
                'whatsapp_message' => '',
                'faqs' => [
                    ['¿Cuánto cuesta el envío?', 'En Lima el delivery a domicilio cuesta {lima}. A todo el Perú enviamos a la agencia Shalom que elijas por {agencia}. Si recoges en nuestro almacén, el envío es gratis.'],
                    ['¿Cómo funcionan los combos?', 'En la sección Combos eliges el combo, escoges el color y la talla de cada prenda y lo agregas al carrito con su precio especial. También puedes armar un look completo en «¡Arma tu Outfit!».'],
                    ['¿Qué métodos de pago aceptan?', 'Puedes pagar contraentrega o, al finalizar tu compra, con Yape, Plin o transferencia bancaria.'],
                    ['¿Puedo cambiar la talla de una prenda?', 'Escríbenos con tu número de pedido y la talla que necesitas, y te indicamos cómo hacer el cambio.'],
                    ['¿Cómo sé qué talla me queda?', 'Cada producto muestra sus tallas disponibles. Si tienes dudas, escríbenos con tu altura y peso y te recomendamos la talla ideal.'],
                ],
            ],
            'hero' => [
                'hombre' => [
                    'eyebrow' => 'Colección hombre',
                    'title' => 'Nacido del océano',
                    'accent' => 'Hecho para dominar',
                    'copy' => 'Prendas esenciales con carácter, precisión y fuerza.',
                ],
                'mujer' => [
                    'eyebrow' => 'Colección mujer',
                    'title' => 'Nacida del arrecife',
                    'accent' => 'Hecha para brillar',
                    'copy' => 'Básicos suaves, luminosos y con actitud para cada día.',
                ],
            ],
            'home' => [
                'qualities' => [
                    ['title' => 'No destiñe', 'text' => 'Mantiene su color lavado tras lavado.'],
                    ['title' => 'No encoge', 'text' => 'Conserva su forma original siempre.'],
                    ['title' => 'No hace bolitas', 'text' => 'Mayor durabilidad en el uso diario.'],
                ],
                'fabrics' => [
                    ['name' => 'Pique', 'text' => 'Textura de nido de abeja, transpirable'],
                    ['name' => 'Waffle', 'text' => 'Relieve en cuadros que mantiene la forma'],
                    ['name' => 'Jersey', 'text' => 'Suave, ligero y con caída natural'],
                ],
                'benefits' => [
                    ['title' => 'Envíos a todo el Perú', 'text' => 'Lima: delivery {lima}. Agencia Shalom {agencia} a todo el Perú. Recojo en almacén gratis.', 'cta' => 'Ver políticas de envío'],
                    ['title' => 'Pago contraentrega', 'text' => 'Paga al recibir o con Yape, Plin y cualquier billetera que lea QR.', 'cta' => 'Ver métodos de pago'],
                    ['title' => 'Atención al cliente', 'text' => 'Te ayudamos antes, durante y después de tu compra.', 'cta' => 'Escríbenos por WhatsApp'],
                ],
            ],
            'legal' => ['note' => LegalDocuments::DRAFT_NOTE, 'hide_note' => false],
        ];
    }

    /** Lo guardado en el panel (sin valores vacíos, que vuelven al texto por defecto). */
    public static function saved(): array
    {
        return PageContent::contentFor(self::SLUG);
    }

    /** Valor de una ruta («envio.home»): lo editado o, si está vacío, el valor por defecto. */
    public static function get(string $path, mixed $default = null): mixed
    {
        $saved = Arr::get(self::saved(), $path);

        if ($saved !== null && $saved !== '' && $saved !== []) {
            return $saved;
        }

        return Arr::get(self::defaults(), $path, $default);
    }

    /** Texto de una ruta con las marcas de precio ya reemplazadas. */
    public static function text(string $path): string
    {
        return self::t((string) self::get($path, ''));
    }

    /** Reemplaza las marcas ({lima}, {agencia}, {desde}, {separo}, {separo_lima}, {combo}) por los valores vigentes. */
    public static function t(string $text): string
    {
        if (! str_contains($text, '{')) {
            return $text;
        }

        return strtr($text, [
            '{lima}' => self::money(Shipping::rate('domicilio')),
            '{agencia}' => self::money(Shipping::rate('agencia')),
            '{desde}' => self::money(Shipping::cheapest()),
            '{separo}' => self::money(Checkout::depositFor('agencia')),
            '{separo_lima}' => self::money(Checkout::depositFor('domicilio')),
            '{combo}' => (string) self::get('promos.combo'),
        ]);
    }

    /** «S/ 15» (sin decimales si es entero) o «S/ 15.50». */
    public static function money(float $amount): string
    {
        return 'S/ '.(floor($amount) === $amount ? number_format($amount, 0) : number_format($amount, 2));
    }

    public static function number(string $path): float
    {
        return (float) self::get($path, 0);
    }

    /** Datos del proveedor tal como se muestran en los documentos legales (por confirmar si faltan). */
    public static function provider(): array
    {
        return collect(LegalDocuments::PROVIDER_FIELDS)
            ->map(fn (string $label, string $key) => trim((string) self::get('proveedor.'.$key)) ?: '[por confirmar]')
            ->all();
    }

    /** Preguntas frecuentes (con marcas de precio ya reemplazadas). */
    public static function faqs(): array
    {
        return collect(self::get('contacto.faqs', []))
            ->map(fn (array $faq) => [self::t((string) $faq[0]), self::t((string) $faq[1])])
            ->all();
    }

    /**
     * Fotos de la tienda que se pueden reemplazar desde el panel: clave => [archivo original en public/, etiqueta, grupo].
     * Una foto subida reemplaza al archivo original en todas las páginas donde se usa (ver asset()); sin subir nada
     * se sigue usando el archivo original.
     */
    public const IMAGES = [
        // clave => [archivo original, etiqueta, zona, marca a la que pertenece (null = compartida por toda la tienda)]
        'hero_hombre' => ['images/home/hero-model-hombre.webp', 'Modelo (se ve cuando no hay banners activos)', 'Portada', 'overshark'],
        'hero_mujer' => ['images/home-mujer/hero.webp', 'Fondo (1440 × 482; se ve cuando no hay banners activos)', 'Portada', 'overshark-girls'],
        'bravos_hero' => ['images/bravos/hero.webp', 'Fondo', 'Portada', 'bravos'],
        'bravos_modelo' => ['images/bravos/modelo.webp', 'Modelo', 'Portada', 'bravos'],
        'bravos_boxy' => ['images/bravos/boxy-fit.webp', 'Boxy Fit', 'Fotos de la página', 'bravos'],
        'site_logo' => ['images/explorar/logo-overshark.png', 'Logo de la pantalla de elección Hombre/Mujer', 'Marca del sitio', null],
        'site_icon' => [null, 'Ícono de la pestaña del navegador (favicon de la tienda y del panel)', 'Marca del sitio', null],
        'explorar_mar' => ['images/explorar/mar.jpg', 'Fondo de mar', 'Pantalla de elección', null],
        'explorar_hombre' => ['images/explorar/hombre-modelos.png', 'Modelos de Hombre', 'Pantalla de elección', 'overshark'],
        'explorar_mujer' => ['images/explorar/mujer-modelos.jpg', 'Modelos de Mujer', 'Pantalla de elección', 'overshark-girls'],
        'promo_hombre' => ['images/home-hombre/promo.webp', 'Promo Wafflera', 'Promo Wafflera', 'overshark'],
        'promo_mujer' => ['images/home-mujer/promo.webp', 'Promo Wafflera', 'Promo Wafflera', 'overshark-girls'],
        'cat_h_polos' => ['images/home-hombre/categoria-polos.webp', 'Polos', 'Categorías de la home', 'overshark'],
        'cat_h_camisas' => ['images/home-hombre/categoria-camisas.webp', 'Camisas', 'Categorías de la home', 'overshark'],
        'cat_h_poleras' => ['images/home-hombre/categoria-poleras.webp', 'Poleras', 'Categorías de la home', 'overshark'],
        'cat_h_joggers' => ['images/home-hombre/categoria-joggers.webp', 'Joggers', 'Categorías de la home', 'overshark'],
        'cat_m_baby' => ['images/home-mujer/categoria-baby-tees.webp', 'Baby tees', 'Categorías de la home', 'overshark-girls'],
        'cat_m_tops' => ['images/home-mujer/categoria-tops.webp', 'Tops', 'Categorías de la home', 'overshark-girls'],
        'cat_m_poleras' => ['images/home-mujer/categoria-poleras.webp', 'Poleras', 'Categorías de la home', 'overshark-girls'],
        'cat_m_joggers' => ['images/home-mujer/categoria-joggers.webp', 'Joggers', 'Categorías de la home', 'overshark-girls'],
        'tela_h_pique' => ['images/home-hombre/tela-pique.webp', 'Pique', 'Telas', 'overshark'],
        'tela_h_waffle' => ['images/home-hombre/tela-waffle.webp', 'Waffle', 'Telas', 'overshark'],
        'tela_h_jersey' => ['images/home-hombre/tela-jersey.webp', 'Jersey', 'Telas', 'overshark'],
        'tela_m_pique' => ['images/home-mujer/tela-pique.webp', 'Pique', 'Telas', 'overshark-girls'],
        'tela_m_waffle' => ['images/home-mujer/tela-waffle.webp', 'Waffle', 'Telas', 'overshark-girls'],
        'tela_m_jersey' => ['images/home-mujer/tela-jersey.webp', 'Jersey', 'Telas', 'overshark-girls'],
        'confianza_envios' => ['images/home-hombre/confianza-envios.webp', 'Envíos a todo el Perú', 'Compra con confianza', null],
        'confianza_pago' => ['images/home-hombre/confianza-pago.webp', 'Pago contraentrega', 'Compra con confianza', null],
        'confianza_atencion' => ['images/home-hombre/confianza-atencion.webp', 'Atención al cliente', 'Compra con confianza', null],
        'outfit_h' => ['images/home-hombre/outfit-principal.webp', 'Arma tu outfit', 'Arma tu outfit (home)', 'overshark'],
        'outfit_m' => ['images/home-mujer/outfit-principal.webp', 'Arma tu outfit', 'Arma tu outfit (home)', 'overshark-girls'],
    ];

    /** Dónde se ve cada grupo de IMAGES (para el inventario de Admin → Banners). */
    public const IMAGE_WHERE = [
        'Portada' => 'Primera imagen del inicio de la marca. Un banner activo la reemplaza.',
        'Fotos de la página' => 'Fotos dentro de la página de la marca.',
        'Marca del sitio' => 'Pestaña del navegador (tienda y panel) y logo de la pantalla de elección Hombre/Mujer. PNG, JPG, WEBP, SVG o ICO.',
        'Pantalla de elección' => 'Pantalla inicial donde la clienta elige Hombre o Mujer.',
        'Promo Wafflera' => 'Banner «Promo Wafflera» del inicio.',
        'Categorías de la home' => 'Sección «Categorías» del inicio.',
        'Telas' => 'Sección «Por qué elegir Overshark» (telas) del inicio.',
        'Compra con confianza' => 'Sección «Compra con confianza» del inicio de Hombre y de Mujer.',
        'Arma tu outfit (home)' => 'Bloque «Arma tu outfit» del inicio.',
    ];

    /** Sube una foto para el slot (y borra la anterior). */
    public static function replaceImage(string $key, \Illuminate\Http\UploadedFile $file): void
    {
        abort_unless(isset(self::IMAGES[$key]), 404);

        $page = PageContent::query()->firstOrNew(['slug' => self::SLUG]);
        $content = $page->content ?? [];

        if (! empty($content['images'][$key])) {
            Storage::disk('public')->delete($content['images'][$key]);
        }

        $content['images'][$key] = ImageOptimizer::storeUploaded($file, 'tienda', ImageOptimizer::BANNER_MAX_SIDE);
        $page->fill(['content' => $content])->save();
    }

    /** Quita la foto subida del slot: vuelve la original. */
    public static function restoreImage(string $key): void
    {
        abort_unless(isset(self::IMAGES[$key]), 404);

        $page = PageContent::query()->where('slug', self::SLUG)->first();
        $content = $page?->content ?? [];

        if (! empty($content['images'][$key])) {
            Storage::disk('public')->delete($content['images'][$key]);
            unset($content['images'][$key]);
            $page->fill(['content' => $content])->save();
        }
    }

    /** URL de una foto editable por su clave: la subida en el panel o, si no hay, el archivo original; null si falta. */
    public static function imageUrl(string $key): ?string
    {
        $uploaded = Arr::get(self::saved(), "images.{$key}");

        if ($uploaded && Storage::disk('public')->exists($uploaded)) {
            return '/storage/'.$uploaded;
        }

        $default = self::IMAGES[$key][0] ?? null;

        return $default && is_file(public_path($default)) ? asset($default) : null;
    }

    /**
     * Sustituto de asset() para las fotos de la tienda: si el archivo es una de las IMAGES y el admin subió otra, devuelve
     * la subida; en cualquier otro caso, la URL normal del archivo.
     */
    public static function asset(string $path): string
    {
        foreach (self::IMAGES as $key => [$default]) {
            if ($default === $path) {
                return self::imageUrl($key) ?? asset($path);
            }
        }

        return asset($path);
    }

    /**
     * Campos del panel, por grupo: [ruta, etiqueta, tipo (text|textarea|number|email), máximo, ayuda].
     * Las preguntas frecuentes y los documentos legales se arman aparte (varias filas).
     */
    public static function schema(): array
    {
        $marks = 'Puedes usar {lima}, {agencia}, {desde}, {separo}, {separo_lima} y {combo}: se reemplazan por los valores vigentes.';

        return [
            'promos' => ['title' => 'Barra superior y promociones', 'help' => 'La barra superior de toda la tienda y el banner «Promo Wafflera» de la portada. '.$marks, 'fields' => [
                ['promos.combo', 'Precio del combo', 'text', 40, 'Ej. 3 × S/ 50. Se muestra como «Combos 3 × S/ 50» y en las páginas de la tienda.'],
                ['promos.anuncio_pago', 'Mensaje de pago (barra superior)', 'text', 60, 'El mensaje de envío se arma solo con el precio de envío más barato.'],
                ['promos.wafflera_title', 'Promo Wafflera · título', 'text', 60, ''],
                ['promos.wafflera_text', 'Promo Wafflera · texto', 'textarea', 200, ''],
            ]],
            'envio' => ['title' => 'Envíos y separo', 'help' => 'Estos importes son los que se cobran en el checkout y los que se muestran en toda la tienda. Un envío en 0 es gratis. El recojo en almacén siempre es gratis.', 'fields' => [
                ['envio.home', 'Envío a domicilio en Lima (S/)', 'number', 999, ''],
                ['envio.agency', 'Envío a agencia Shalom (S/)', 'number', 999, ''],
                ['envio.deposit_home', 'Separo contraentrega a domicilio (S/)', 'number', 999, 'Se paga en el checkout; el saldo se paga al recibir.'],
                ['envio.deposit_agency', 'Separo contraentrega en agencia (S/)', 'number', 999, 'Incluye el envío y el separo.'],
            ]],
            'contacto' => ['title' => 'Contacto', 'help' => 'Datos de contacto del pie de página, de la página Contacto y del botón flotante de WhatsApp.', 'fields' => [
                ['contacto.email', 'Correo de atención', 'email', 120, ''],
                ['contacto.horario', 'Horario de atención', 'text', 80, ''],
                ['contacto.whatsapp', 'Número de WhatsApp', 'phone', 20, 'Un celular peruano de 9 dígitos se completa con +51. Si lo dejas vacío, el botón de WhatsApp no se muestra.'],
                ['contacto.whatsapp_message', 'Mensaje inicial de WhatsApp', 'text', 300, 'Lo que aparece escrito al abrir el chat. Vacío = «Hola Overshark, quiero información sobre sus productos.»'],
            ]],
            'proveedor' => ['title' => 'Datos del proveedor', 'help' => 'Aparecen en Términos y condiciones, Política de privacidad y Libro de Reclamaciones. Mientras estén vacíos se muestra «[por confirmar]».', 'fields' => [
                ['proveedor.razon_social', 'Razón social', 'text', 160, ''],
                ['proveedor.ruc', 'RUC', 'text', 11, '11 dígitos.'],
                ['proveedor.domicilio', 'Domicilio', 'text', 200, ''],
                ['proveedor.canal', 'Canal de atención', 'text', 160, 'Ej. correo o WhatsApp donde atiendes reclamos y derechos de datos.'],
            ]],
            'home' => ['title' => 'Secciones de la portada', 'help' => 'Los tres bloques «No destiñe…», las telas y «Compra con confianza» de las portadas de Hombre y Mujer. Las fotos se cambian en «Fotos de la tienda». '.$marks, 'fields' => [
                ['home.qualities.0.title', 'Calidad 1 · título', 'text', 40, ''], ['home.qualities.0.text', 'Calidad 1 · texto', 'text', 100, ''],
                ['home.qualities.1.title', 'Calidad 2 · título', 'text', 40, ''], ['home.qualities.1.text', 'Calidad 2 · texto', 'text', 100, ''],
                ['home.qualities.2.title', 'Calidad 3 · título', 'text', 40, ''], ['home.qualities.2.text', 'Calidad 3 · texto', 'text', 100, ''],
                ['home.fabrics.0.name', 'Tela 1 · nombre', 'text', 30, ''], ['home.fabrics.0.text', 'Tela 1 · texto', 'text', 80, ''],
                ['home.fabrics.1.name', 'Tela 2 · nombre', 'text', 30, ''], ['home.fabrics.1.text', 'Tela 2 · texto', 'text', 80, ''],
                ['home.fabrics.2.name', 'Tela 3 · nombre', 'text', 30, ''], ['home.fabrics.2.text', 'Tela 3 · texto', 'text', 80, ''],
                ['home.benefits.0.title', 'Confianza 1 · título', 'text', 50, ''], ['home.benefits.0.text', 'Confianza 1 · texto', 'textarea', 160, ''], ['home.benefits.0.cta', 'Confianza 1 · botón', 'text', 40, ''],
                ['home.benefits.1.title', 'Confianza 2 · título', 'text', 50, ''], ['home.benefits.1.text', 'Confianza 2 · texto', 'textarea', 160, ''], ['home.benefits.1.cta', 'Confianza 2 · botón', 'text', 40, ''],
                ['home.benefits.2.title', 'Confianza 3 · título', 'text', 50, ''], ['home.benefits.2.text', 'Confianza 3 · texto', 'textarea', 160, ''], ['home.benefits.2.cta', 'Confianza 3 · botón', 'text', 40, ''],
            ]],
            'hero' => ['title' => 'Portadas de Hombre y Mujer', 'help' => 'Textos grandes de la portada de cada línea. La foto de Hombre se cambia en Banners.', 'fields' => [
                ['hero.hombre.eyebrow', 'Hombre · etiqueta', 'text', 60, ''],
                ['hero.hombre.title', 'Hombre · título', 'text', 60, ''],
                ['hero.hombre.accent', 'Hombre · segunda línea (color)', 'text', 60, ''],
                ['hero.hombre.copy', 'Hombre · texto', 'textarea', 160, ''],
                ['hero.mujer.eyebrow', 'Mujer · etiqueta', 'text', 60, ''],
                ['hero.mujer.title', 'Mujer · título', 'text', 60, ''],
                ['hero.mujer.accent', 'Mujer · segunda línea (color)', 'text', 60, ''],
                ['hero.mujer.copy', 'Mujer · texto', 'textarea', 160, ''],
            ]],
        ];
    }
}

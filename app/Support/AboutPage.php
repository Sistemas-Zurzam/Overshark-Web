<?php

namespace App\Support;

use App\Models\PageContent;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

/**
 * Página «Nosotros» (diseño Figma «04 · Nosotros»): textos por defecto, lo editado en Admin → Página Nosotros y las fotos.
 * Cada foto puede subirse desde el admin (disco público nosotros/) o, si no, se usa
 * public/images/nosotros/{nombre}.(webp|jpg|png); sin ninguna, la vista muestra un espacio reservado.
 */
class AboutPage
{
    public const SLUG = 'nosotros';

    /** Fotos: clave => [nombre de archivo por defecto, para qué sirve]. */
    public const IMAGES = [
        'portada' => ['portada-mar', 'Portada (fondo)'],
        'historia_1' => ['historia-1', 'Historia · foto grande'],
        'historia_2' => ['historia-2', 'Historia · foto pequeña'],
        'paso_1' => ['proceso-tejido', 'Paso 1'],
        'paso_2' => ['proceso-tenido', 'Paso 2'],
        'paso_3' => ['proceso-corte', 'Paso 3'],
        'paso_4' => ['proceso-confeccion', 'Paso 4'],
        'paso_5' => ['proceso-acabados', 'Paso 5'],
        'paso_6' => ['proceso-empaque', 'Paso 6'],
        'marca_overshark' => ['marca-overshark', 'Marca · Overshark'],
        'marca_girls' => ['marca-girls', 'Marca · Overshark Girls'],
        'marca_bravos' => ['marca-bravos', 'Marca · Bravos'],
    ];

    public static function defaults(): array
    {
        return [
            'seo_description' => 'Overshark fabrica sus prendas de principio a fin: tejido, teñido, corte, confección, acabados, control de calidad y empaque.',
            'hero' => [
                'tag' => 'Nosotros',
                'title' => 'Somos Overshark',
                'text' => 'Ganadores, salvajes y elegantes. Como el tiburón: más bravos y más grandes.',
            ],
            'historia' => [
                'tag' => 'Nuestra historia',
                'title' => 'Básicos que se sienten como un triunfo',
                'text' => "Overshark nació para que vestirse bien todos los días no sea complicado ni caro. Hacemos baby tees, tops, polos y joggers con cortes que favorecen y telas que aguantan el uso diario.\n\nCreemos en la ropa que te da actitud: prendas simples que combinan con todo y que puedes armar en combos para renovar tu clóset sin pensarlo dos veces.",
                // Opcionales: si están vacíos no se muestran (el sello «Desde» y el párrafo de origen).
                'origen' => '',
                'year' => '',
                'button' => 'Conoce la tienda',
                'image_alt_1' => 'Modelo con polo blanco y jogger azul',
                'image_alt_2' => 'Modelo con top y jogger rosados de Overshark Girls',
            ],
            'adn' => [
                'title' => 'Nuestro ADN',
                'text' => 'Tres palabras que guían cada prenda',
                'items' => [
                    ['title' => 'Ganadora', 'text' => 'Prendas que te hacen sentir seguro desde que te las pones. Cortes pensados para favorecer.'],
                    ['title' => 'Salvaje', 'text' => 'Actitud sin filtro. Colores que se notan y básicos que rompen la rutina.'],
                    ['title' => 'Elegante', 'text' => 'Simples, limpias y bien terminadas. Lo básico también puede verse premium.'],
                ],
            ],
            'proceso' => [
                'tag' => 'Del hilo a la prenda',
                'title' => 'Así creamos lo que vistes.',
                'text' => 'Un proceso conectado, con atención en cada etapa.',
                'steps' => [
                    ['title' => 'Tejido', 'text' => 'Damos forma a la tela, cuidando su estructura y textura.', 'image_alt' => 'Telar textil tejiendo tela de punto'],
                    ['title' => 'Teñido', 'text' => 'Desarrollamos los colores que dan identidad a cada colección.', 'image_alt' => 'Tela recién teñida en proceso de coloración'],
                    ['title' => 'Corte', 'text' => 'Preparamos las piezas que definen la forma de cada prenda.', 'image_alt' => 'Piezas de tela cortadas sobre la mesa de corte'],
                    ['title' => 'Confección', 'text' => 'Unimos cada pieza con atención a las costuras y la construcción.', 'image_alt' => 'Máquina de coser uniendo las piezas de una prenda'],
                    ['title' => 'Acabados y calidad', 'text' => 'Revisamos las terminaciones y los detalles antes de preparar cada prenda.', 'image_alt' => 'Revisión de costuras y terminaciones de una prenda'],
                    ['title' => 'Empaque', 'text' => 'Doblamos, protegemos y preparamos las prendas para su siguiente destino.', 'image_alt' => 'Prendas dobladas y embolsadas listas para su envío'],
                ],
            ],
            'datos' => [
                ['value' => 'S/ 14', 'label' => 'Envío único a todo el Perú'],
                ['value' => '3 × S/ 50', 'label' => 'Combos todo el año'],
                ['value' => '3 marcas', 'label' => 'Overshark, Girls y Bravos'],
                ['value' => 'L – V', 'label' => 'Atención de 9am a 6pm'],
            ],
            'marcas' => [
                'tag' => 'Familia Overshark',
                'title' => 'Una familia, tres marcas',
                'text' => 'Cada una con su propio estilo y la misma forma de hacer las cosas',
            ],
            'comunidad' => [
                'title' => 'Forma parte de la comunidad',
                // Opcionales: la cuenta y los enlaces a redes solo se muestran si están completos.
                'handle' => '',
                'instagram' => '',
                'tiktok' => '',
                'facebook' => '',
            ],
        ];
    }

    /** Contenido final: lo editado encima de los textos por defecto (las listas se reemplazan enteras). */
    public static function content(): array
    {
        return self::merge(self::defaults(), PageContent::contentFor(self::SLUG));
    }

    /** URL de la foto: la subida en el admin o, si no hay, la de public/images/nosotros; null si falta. */
    public static function imageUrl(string $key, array $content): ?string
    {
        $uploaded = Arr::get($content, "images.{$key}");

        if ($uploaded && Storage::disk('public')->exists($uploaded)) {
            return '/storage/'.$uploaded;
        }

        $name = self::IMAGES[$key][0] ?? null;
        $file = $name ? collect(['webp', 'jpg', 'jpeg', 'png'])->map(fn ($ext) => "images/nosotros/{$name}.{$ext}")
            ->first(fn ($path) => is_file(public_path($path))) : null;

        return $file ? asset($file) : null;
    }

    private static function merge(array $defaults, array $saved): array
    {
        foreach ($saved as $key => $value) {
            $defaults[$key] = is_array($value) && isset($defaults[$key]) && is_array($defaults[$key]) && ! array_is_list($defaults[$key])
                ? self::merge($defaults[$key], $value)
                : $value;
        }

        return $defaults;
    }
}

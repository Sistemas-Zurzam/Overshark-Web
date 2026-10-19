<?php

namespace App\Support;

use App\Models\PageContent;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

/**
 * Página «Nosotros»: textos por defecto, lo editado en Admin → Página Nosotros y las fotos.
 * Cada foto puede subirse desde el admin (disco público nosotros/) o, si no, se usa
 * public/images/nosotros/{nombre}.(webp|jpg|png); sin ninguna, la vista muestra un espacio reservado.
 */
class AboutPage
{
    public const SLUG = 'nosotros';

    /** Fotos: clave => [nombre de archivo por defecto, para qué sirve]. */
    public const IMAGES = [
        'portada' => ['portada-taller', 'Portada (horizontal)'],
        'esencia' => ['esencia-tela', 'Nuestra esencia'],
        'paso_1' => ['proceso-tejido', 'Paso 1'],
        'paso_2' => ['proceso-tenido', 'Paso 2'],
        'paso_3' => ['proceso-corte', 'Paso 3'],
        'paso_4' => ['proceso-confeccion', 'Paso 4'],
        'paso_5' => ['proceso-acabados', 'Paso 5'],
        'paso_6' => ['proceso-empaque', 'Paso 6'],
        'calidad' => ['calidad-inspeccion', 'Atención al detalle'],
    ];

    public static function defaults(): array
    {
        return [
            'seo_description' => 'Overshark fabrica sus prendas de principio a fin: tejido, teñido, corte, confección, acabados, control de calidad y empaque.',
            'hero' => [
                'tag' => 'Somos Overshark',
                'title' => 'Creamos tu estilo desde el',
                'highlight' => 'primer hilo.',
                'text' => 'Del tejido al acabado final, realizamos cada etapa para crear prendas con identidad, comodidad y atención al detalle.',
                'button' => 'Conoce nuestro proceso',
                'attributes' => ['Fabricación integral', 'Tejido y teñido', 'Confección y acabados', 'Control de calidad'],
                'image_alt' => 'Taller textil con máquinas de confección',
            ],
            'esencia' => [
                'tag' => 'Nuestra esencia',
                'title' => 'No solo vendemos prendas.',
                'highlight' => 'Las creamos.',
                'text' => 'En Overshark, cada prenda comienza mucho antes de llegar a tus manos. Trabajamos desde el tejido y el teñido de la tela hasta el corte, la confección y los acabados. Esa conexión con cada etapa nos permite cuidar los detalles que dan forma a nuestra ropa.',
                'image_alt' => 'Rollos de tela de punto listos para el corte',
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
            'calidad' => [
                'tag' => 'Atención al detalle',
                'title' => 'La calidad se trabaja',
                'highlight' => 'en cada etapa.',
                'text' => 'Revisamos la construcción y los acabados de nuestras prendas para cuidar su presentación y comodidad. Cada detalle forma parte del resultado que queremos entregar.',
                'points' => ['Textura y presentación de la tela', 'Costuras y uniones', 'Cuellos y terminaciones', 'Revisión final y empaque'],
                'image_alt' => 'Revisión manual de las costuras de una prenda terminada',
            ],
            'conectada' => [
                'tag' => 'Una experiencia conectada',
                'title' => 'De nuestra producción',
                'highlight' => 'a tu outfit.',
                'text' => 'Explora nuestras prendas, descubre sus colores y combina tu estilo desde nuestra tienda.',
                'note' => 'En «Arma tu outfit» eliges un combo y escoges el color y la talla de cada prenda, con un precio especial por el conjunto.',
                'button_collection' => 'Explorar colección',
                'button_outfit' => 'Arma tu outfit',
                'show_products' => true,
            ],
            'cierre' => [
                'title' => 'Hecho por nosotros.',
                'highlight' => 'Elegido por ti.',
                'text' => 'Conoce la colección Overshark y encuentra las prendas que van contigo.',
                'button_primary' => 'Ver colección',
                'button_secondary' => 'Contáctanos',
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

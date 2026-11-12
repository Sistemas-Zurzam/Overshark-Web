<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageContent;
use App\Support\ImageOptimizer;
use App\Support\LegalDocuments;
use App\Support\SiteContent;
use App\Support\StorefrontCategories;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Admin → Contenido de la tienda: barra superior, precios de envío y separo, contacto y preguntas frecuentes, datos del
 * proveedor, textos de portada y documentos legales (App\Support\SiteContent). Un campo vacío vuelve al valor por defecto.
 */
class SiteContentController extends Controller
{
    public function edit(): View
    {
        $faqs = collect(SiteContent::get('contacto.faqs', []))->pad(SiteContent::FAQ_SLOTS, [null, null])->take(SiteContent::FAQ_SLOTS)->values();

        return view('admin.contenido.edit', [
            'schema' => SiteContent::schema(),
            'values' => fn (string $path) => SiteContent::get($path),
            'faqs' => $faqs,
            'categories' => collect(['hombre', 'mujer'])->mapWithKeys(fn (string $line) => [$line => ['categories' => StorefrontCategories::all($line)]]),
            'legal' => LegalDocuments::all(),
            'legalDefaults' => LegalDocuments::defaults(),
            // Fotos agrupadas por zona de la tienda (cada una reemplaza al archivo original donde se usa).
            'images' => collect(SiteContent::IMAGES)->map(fn (array $meta, string $key) => [
                'label' => $meta[1],
                'group' => $meta[2],
                'url' => SiteContent::imageUrl($key),
                'uploaded' => (bool) Arr::get(SiteContent::saved(), "images.{$key}"),
            ])->groupBy('group'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $rules = [
            'c.legal.note' => ['nullable', 'string', 'max:300'],
            'c.legal.hide_note' => ['nullable', 'boolean'],
            'c.contacto.faq' => ['nullable', 'array', 'max:'.SiteContent::FAQ_SLOTS],
            'c.contacto.faq.*.q' => ['nullable', 'string', 'max:200', 'required_with:c.contacto.faq.*.a'],
            'c.contacto.faq.*.a' => ['nullable', 'string', 'max:800', 'required_with:c.contacto.faq.*.q'],
            'images.*' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:8192'],
            'remove_images' => ['nullable', 'array'],
        ];

        foreach (SiteContent::schema() as $group) {
            foreach ($group['fields'] as [$path, , $type, $max]) {
                $rules['c.'.$path] = match ($type) {
                    'toggle' => ['nullable', 'boolean'],
                    'number' => ['nullable', 'numeric', 'min:0', 'max:999'],
                    'phone' => ['nullable', 'string', 'max:'.$max, 'regex:/^[+\d\s()-]*$/'],
                    'email' => ['nullable', 'email', 'max:'.$max],
                    default => ['nullable', 'string', 'max:'.$max],
                };
            }
        }

        $rules['c.categorias.*.*.label'] = ['nullable', 'string', 'max:40'];
        $rules['c.categorias.*.*.terms'] = ['nullable', 'string', 'max:500'];

        foreach (LegalDocuments::defaults() as $key => $doc) {
            foreach (array_keys($doc['sections']) as $index) {
                $rules["c.legal.{$key}.{$index}.h"] = ['nullable', 'string', 'max:160'];
                $rules["c.legal.{$key}.{$index}.t"] = ['nullable', 'string', 'max:2500'];
            }
        }

        $validated = $request->validate($rules, [
            'images.*.image' => 'La foto debe ser PNG, JPG o WEBP.',
            'images.*.max' => 'La foto puede pesar como máximo 8 MB.',
            'c.contacto.faq.*.q.required_with' => 'Escribe también la pregunta.',
            'c.contacto.faq.*.a.required_with' => 'Escribe también la respuesta.',
            'c.*.email' => 'Escribe un correo válido.',
            'c.contacto.whatsapp.regex' => 'Escribe solo números (puedes incluir +51, espacios o guiones).',
        ]);

        $input = $validated['c'] ?? [];
        $defaults = SiteContent::defaults();
        $content = [];

        // Solo se guarda lo que cambia respecto al valor por defecto: así lo que se mejore en el código llega a quien no lo editó.
        foreach (SiteContent::schema() as $group) {
            foreach ($group['fields'] as [$path, , $type]) {
                $value = Arr::get($input, $path);

                // Casilla: solo se guarda encendida (apagada es el valor por defecto).
                if ($type === 'toggle') {
                    if ($value) {
                        Arr::set($content, $path, true);
                    }

                    continue;
                }

                if ($value === null || trim((string) $value) === '') {
                    continue;
                }

                $value = $type === 'number' ? (float) $value : trim((string) $value);

                if ($value !== Arr::get($defaults, $path)) {
                    Arr::set($content, $path, $value);
                }
            }
        }

        $faqs = collect($input['contacto']['faq'] ?? [])
            ->map(fn (array $row) => [trim((string) ($row['q'] ?? '')), trim((string) ($row['a'] ?? ''))])
            ->filter(fn (array $row) => $row[0] !== '' && $row[1] !== '')
            ->values()
            ->all();

        if ($faqs !== [] && $faqs !== $defaults['contacto']['faqs']) {
            Arr::set($content, 'contacto.faqs', $faqs);
        }

        foreach (LegalDocuments::defaults() as $key => $doc) {
            foreach ($doc['sections'] as $index => [$heading, $text]) {
                foreach (['h' => $heading, 't' => $text] as $field => $default) {
                    $value = trim((string) ($input['legal'][$key][$index][$field] ?? ''));

                    if ($value !== '' && $value !== $default) {
                        Arr::set($content, "legal.{$key}.{$index}.{$field}", $value);
                    }
                }
            }
        }

        foreach (StorefrontCategories::defaults() as $line => $config) {
            foreach ($config['categories'] as $slug => $category) {
                $label = trim((string) ($input['categorias'][$line][$slug]['label'] ?? ''));
                $terms = StorefrontCategories::parseTerms((string) ($input['categorias'][$line][$slug]['terms'] ?? ''));

                if ($label !== '' && $label !== $category['label']) {
                    Arr::set($content, "categorias.{$line}.{$slug}.label", $label);
                }

                if ($terms !== [] && $terms !== $category['terms']) {
                    Arr::set($content, "categorias.{$line}.{$slug}.terms", implode(', ', $terms));
                }
            }
        }

        $note = trim((string) ($input['legal']['note'] ?? ''));
        if ($note !== '' && $note !== LegalDocuments::DRAFT_NOTE) {
            Arr::set($content, 'legal.note', $note);
        }
        if (! empty($input['legal']['hide_note'])) {
            Arr::set($content, 'legal.hide_note', true);
        }

        $page = PageContent::query()->firstOrNew(['slug' => SiteContent::SLUG]);
        $content['images'] = $this->storeImages($request, $page->content['images'] ?? []);
        $page->fill(['content' => $content])->save();

        return back()->with('status', 'Contenido de la tienda actualizado.');
    }

    /** Restaura todos los textos y precios al valor por defecto; las fotos subidas se conservan. */
    public function reset(): RedirectResponse
    {
        $page = PageContent::query()->where('slug', SiteContent::SLUG)->first();
        $page?->fill(['content' => ['images' => $page->content['images'] ?? []]])->save();

        return back()->with('status', 'Se restauraron los textos y precios originales de la tienda.');
    }

    /** Sube o quita las fotos de portada y devuelve las vigentes. */
    private function storeImages(Request $request, array $stored): array
    {
        $disk = Storage::disk('public');

        foreach (array_keys(SiteContent::IMAGES) as $key) {
            if ($request->hasFile("images.{$key}")) {
                if (! empty($stored[$key])) {
                    $disk->delete($stored[$key]);
                }
                $stored[$key] = ImageOptimizer::storeUploaded($request->file("images.{$key}"), 'tienda', ImageOptimizer::BANNER_MAX_SIDE);
            } elseif ($request->boolean("remove_images.{$key}") && ! empty($stored[$key])) {
                $disk->delete($stored[$key]);
                unset($stored[$key]);
            }
        }

        return $stored;
    }
}

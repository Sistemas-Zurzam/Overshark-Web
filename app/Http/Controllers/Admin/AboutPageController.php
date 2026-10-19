<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageContent;
use App\Support\AboutPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/** Admin → Página Nosotros: textos y fotos de /nosotros. */
class AboutPageController extends Controller
{
    public function edit(): View
    {
        $content = AboutPage::content();

        return view('admin.about.edit', [
            'c' => $content,
            'images' => collect(AboutPage::IMAGES)->map(fn ($meta, $key) => [
                'label' => $key === 'portada' || $key === 'esencia' || $key === 'calidad' ? $meta[1] : $meta[1].' · '.($content['proceso']['steps'][(int) substr($key, -1) - 1]['title'] ?? ''),
                'url' => AboutPage::imageUrl($key, $content),
                'uploaded' => (bool) Arr::get($content, "images.{$key}"),
                'file' => $meta[0],
            ]),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $text = ['required', 'string', 'max:500'];
        $short = ['required', 'string', 'max:120'];
        $validated = $request->validate([
            'seo_description' => ['nullable', 'string', 'max:300'],
            'hero.tag' => $short, 'hero.title' => $short, 'hero.highlight' => ['nullable', 'string', 'max:120'], 'hero.text' => $text,
            'hero.button' => $short, 'hero.attributes' => ['array', 'size:4'], 'hero.attributes.*' => $short, 'hero.image_alt' => $short,
            'esencia.tag' => $short, 'esencia.title' => $short, 'esencia.highlight' => ['nullable', 'string', 'max:120'],
            'esencia.text' => ['required', 'string', 'max:1500'], 'esencia.image_alt' => $short,
            'proceso.tag' => $short, 'proceso.title' => $short, 'proceso.text' => $text,
            'proceso.steps' => ['array', 'size:6'], 'proceso.steps.*.title' => $short, 'proceso.steps.*.text' => $text, 'proceso.steps.*.image_alt' => $short,
            'calidad.tag' => $short, 'calidad.title' => $short, 'calidad.highlight' => ['nullable', 'string', 'max:120'],
            'calidad.text' => ['required', 'string', 'max:1500'], 'calidad.points' => ['array', 'size:4'], 'calidad.points.*' => $short, 'calidad.image_alt' => $short,
            'conectada.tag' => $short, 'conectada.title' => $short, 'conectada.highlight' => ['nullable', 'string', 'max:120'],
            'conectada.text' => $text, 'conectada.note' => ['nullable', 'string', 'max:500'],
            'conectada.button_collection' => $short, 'conectada.button_outfit' => $short, 'conectada.show_products' => ['nullable', 'boolean'],
            'cierre.title' => $short, 'cierre.highlight' => ['nullable', 'string', 'max:120'], 'cierre.text' => $text,
            'cierre.button_primary' => $short, 'cierre.button_secondary' => $short,
            'images' => ['nullable', 'array'],
            'images.*' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:8192'],
            'remove_images' => ['nullable', 'array'],
        ], [
            'images.*.image' => 'Cada foto debe ser PNG, JPG o WEBP.',
            'images.*.max' => 'Cada foto puede pesar como máximo 8 MB.',
        ]);

        $page = PageContent::query()->firstOrNew(['slug' => AboutPage::SLUG]);
        $stored = $page->content['images'] ?? [];
        $disk = Storage::disk('public');

        foreach (array_keys(AboutPage::IMAGES) as $key) {
            if ($request->hasFile("images.{$key}")) {
                if (! empty($stored[$key])) {
                    $disk->delete($stored[$key]);
                }
                $stored[$key] = $request->file("images.{$key}")->store('nosotros', 'public');
            } elseif ($request->boolean("remove_images.{$key}") && ! empty($stored[$key])) {
                $disk->delete($stored[$key]);
                unset($stored[$key]);
            }
        }

        $content = Arr::except($validated, ['images', 'remove_images']);
        $content['conectada']['show_products'] = $request->boolean('conectada.show_products');
        $content['images'] = $stored;
        $page->fill(['content' => $content])->save();

        return back()->with('status', 'Página Nosotros actualizada.');
    }

    public function reset(): RedirectResponse
    {
        $page = PageContent::query()->where('slug', AboutPage::SLUG)->first();
        $images = $page?->content['images'] ?? [];

        // Se restauran los textos; las fotos subidas se conservan.
        $page?->fill(['content' => ['images' => $images]])->save();

        return back()->with('status', 'Se restauraron los textos originales de la página Nosotros.');
    }
}

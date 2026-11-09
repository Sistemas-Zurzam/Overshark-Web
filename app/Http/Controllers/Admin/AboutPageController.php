<?php

namespace App\Http\Controllers\Admin;

use App\Support\ImageOptimizer;
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
                'label' => str_starts_with($key, 'paso_') ? $meta[1].' · '.($content['proceso']['steps'][(int) substr($key, 5) - 1]['title'] ?? '') : $meta[1],
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
        $optional = ['nullable', 'string', 'max:120'];
        $validated = $request->validate([
            'seo_description' => ['nullable', 'string', 'max:300'],
            'hero.tag' => $short, 'hero.title' => $short, 'hero.text' => $text,
            'historia.tag' => $short, 'historia.title' => $short, 'historia.text' => ['required', 'string', 'max:1500'],
            'historia.origen' => ['nullable', 'string', 'max:1000'], 'historia.year' => ['nullable', 'string', 'max:12'],
            'historia.button' => $short, 'historia.image_alt_1' => $short, 'historia.image_alt_2' => $short,
            'adn.title' => $short, 'adn.text' => $short, 'adn.items' => ['array', 'size:3'], 'adn.items.*.title' => $short, 'adn.items.*.text' => $text,
            'proceso.tag' => $short, 'proceso.title' => $short, 'proceso.text' => $text,
            'proceso.steps' => ['array', 'size:6'], 'proceso.steps.*.title' => $short, 'proceso.steps.*.text' => $text, 'proceso.steps.*.image_alt' => $short,
            'datos' => ['array', 'size:4'], 'datos.*.value' => ['required', 'string', 'max:20'], 'datos.*.label' => $short,
            'marcas.tag' => $short, 'marcas.title' => $short, 'marcas.text' => $short,
            'comunidad.title' => $short, 'comunidad.handle' => $optional,
            'comunidad.instagram' => ['nullable', 'url', 'max:255'], 'comunidad.tiktok' => ['nullable', 'url', 'max:255'], 'comunidad.facebook' => ['nullable', 'url', 'max:255'],
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
                $stored[$key] = ImageOptimizer::storeUploaded($request->file("images.{$key}"), 'nosotros', ImageOptimizer::BANNER_MAX_SIDE);
            } elseif ($request->boolean("remove_images.{$key}") && ! empty($stored[$key])) {
                $disk->delete($stored[$key]);
                unset($stored[$key]);
            }
        }

        $content = Arr::except($validated, ['images', 'remove_images']);
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

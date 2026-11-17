<?php

namespace App\Http\Controllers;

use App\Models\Admin\Producto;
use App\Models\Brand;
use App\Support\StorefrontCategories;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Lo que Google necesita para encontrar la tienda nueva: el sitemap y la redirección permanente de los enlaces de la
 * tienda anterior (/shop/camisero-jersey-…), que Google sigue mostrando y que en esta tienda no existen.
 */
class SeoController extends Controller
{
    /**
     * /shop/{nombre-del-producto}: lleva al producto en venta con ese nombre (camisero-jersey-melon → CAMISERO JERSEY)
     * o, si ya no está, a la búsqueda con las palabras que sí encuentran algo. 301 para que Google cambie el enlace.
     */
    public function legacyShop(?string $slug = null): RedirectResponse
    {
        $words = collect(preg_split('/[-_\s\/]+/u', Str::lower(urldecode((string) $slug))))
            ->map(fn (string $word) => trim($word))
            ->filter(fn (string $word) => $word !== '' && ! ctype_digit($word))
            ->take(8)
            ->values();

        for ($count = $words->count(); $count >= 1; $count--) {
            $name = $words->take($count)->implode(' ');
            $id = $this->forSale()->whereRaw('LOWER(TRIM(productos.name)) = ?', [$name])->orderBy('productos.id')->value('productos.id');
            if ($id) {
                return redirect()->route('web.products.show', $id, 301);
            }
        }

        for ($count = $words->count(); $count >= 1; $count--) {
            $term = $words->take($count)->implode(' ');
            if ($this->forSale()->where('productos.name', 'like', '%'.$term.'%')->exists()) {
                return redirect()->route('web.products.search', ['q' => $term], 301);
            }
        }

        return redirect()->route('web.products.search', [], 301);
    }

    /** sitemap.xml: portada, catálogo, categorías, marcas, páginas fijas y cada producto en venta (una URL por producto). */
    public function sitemap(): Response
    {
        $urls = collect([
            ['loc' => url('/'), 'priority' => '1.0'],
            ['loc' => route('web.products.search'), 'priority' => '0.9'],
            ['loc' => url('/mujer/productos'), 'priority' => '0.9'],
            ['loc' => url('/combos'), 'priority' => '0.7'],
            ['loc' => url('/arma-tu-outfit'), 'priority' => '0.5'],
            ['loc' => url('/nosotros'), 'priority' => '0.4'],
            ['loc' => url('/contacto'), 'priority' => '0.4'],
        ])
            ->merge(collect(StorefrontCategories::all())->keys()
                ->map(fn (string $slug) => ['loc' => route('web.products.search', ['categoria' => $slug]), 'priority' => '0.7']))
            ->merge(Brand::query()->active()->whereNotNull('slug')->get()
                ->map(fn (Brand $brand) => ['loc' => route('web.brands.show', $brand), 'priority' => '0.7']))
            ->merge($this->productUrls());

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $url) {
            $xml .= '  <url><loc>'.e($url['loc']).'</loc>'
                .(isset($url['lastmod']) ? '<lastmod>'.$url['lastmod'].'</lastmod>' : '')
                .'<priority>'.$url['priority'].'</priority></url>'."\n";
        }

        return response($xml.'</urlset>'."\n", 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /** Un producto = sus variantes con el mismo nombre y empresa; la URL usa la primera variante en venta (como el catálogo). */
    private function productUrls(): Collection
    {
        return $this->forSale()
            ->selectRaw('MIN(productos.id) as id, MAX(productos.updated_at) as last_update')
            ->groupBy('productos.zazu_company_id', 'productos.name')
            ->orderBy('id')
            ->get()
            ->map(fn ($product) => [
                'loc' => route('web.products.show', $product->id),
                'lastmod' => $product->last_update ? substr((string) $product->last_update, 0, 10) : null,
                'priority' => '0.8',
            ]);
    }

    /** Variantes que la tienda muestra y vende (mismo criterio que el catálogo y la página de producto). */
    private function forSale(): Builder
    {
        return Producto::query()->where('productos.price', '>', 0)->where('productos.stock', '>', 0)->withColorPhoto();
    }
}

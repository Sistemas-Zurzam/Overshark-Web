<?php

namespace Tests\Feature;

use App\Models\Admin\Producto;
use App\Models\Admin\ProductoColorImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ColorsNeedPhotosTest extends TestCase
{
    use RefreshDatabase;

    private function variant(string $name, string $color, string $size = 'M'): Producto
    {
        return Producto::query()->create([
            'name' => $name, 'default_code' => "{$name}-{$color}-{$size}", 'color' => $color, 'talla' => $size,
            'stock' => 5, 'price' => 30, 'zazu_company_id' => 1, 'empresa_nombre' => 'OVERSHARK PERU S.A.C', 'marca' => 'OVERSHARK',
        ]);
    }

    private function photo(string $name, string $color): void
    {
        ProductoColorImage::query()->create(['product_name' => $name, 'color' => $color, 'images' => ['productos/colores/'.$color.'.webp']]);
    }

    public function test_product_page_only_offers_the_colors_that_have_photos(): void
    {
        $negro = $this->variant('CAMISERO PIKE', 'NEGRO');
        $this->variant('CAMISERO PIKE', 'CAMOTE');
        $this->photo('CAMISERO PIKE', 'NEGRO');

        $this->get(route('web.products.show', $negro))
            ->assertOk()
            ->assertSee('NEGRO')
            ->assertDontSee('CAMOTE');

        // Al subir las fotos del color, aparece.
        $this->photo('CAMISERO PIKE', 'CAMOTE');
        $this->get(route('web.products.show', $negro))->assertOk()->assertSee('CAMOTE');
    }

    public function test_a_product_with_no_color_photos_is_not_listed_until_one_is_uploaded(): void
    {
        $sinFotos = $this->variant('POLO SIN FOTOS', 'AZUL');
        $this->variant('POLO LISTO', 'NEGRO');
        $this->photo('POLO LISTO', 'NEGRO');

        $this->get(route('web.products.search'))->assertOk()->assertSee('Polo Listo')->assertDontSee('Polo Sin Fotos');
        $this->get(route('web.products.show', $sinFotos))->assertNotFound();

        $this->photo('POLO SIN FOTOS', 'AZUL');
        $this->get(route('web.products.search'))->assertSee('Polo Sin Fotos');
    }
}

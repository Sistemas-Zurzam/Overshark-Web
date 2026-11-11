<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateJwt;
use App\Models\Admin\Producto;
use App\Models\Admin\ProductoColorImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Quitar la galería de un color (fotos equivocadas): el color deja de verse en la tienda y los archivos se borran. */
class ColorGalleryRemovalTest extends TestCase
{
    use RefreshDatabase;

    private Producto $plomo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(AuthenticateJwt::class)->actingAs(User::factory()->create());
        Storage::fake('public');

        foreach (['PLOMO', 'AZUL'] as $color) {
            $variant = Producto::query()->create([
                'name' => 'CLASICO', 'default_code' => 'CL-'.$color, 'color' => $color, 'talla' => 'M', 'stock' => 5, 'price' => 17,
                'zazu_company_id' => 1, 'empresa_nombre' => 'OVERSHARK PERU S.A.C',
            ]);
            $this->plomo ??= $variant;

            Storage::disk('public')->put("productos/colores/{$color}.webp", 'x');
            ProductoColorImage::query()->create(['product_name' => 'CLASICO', 'color' => $color, 'images' => ["productos/colores/{$color}.webp"]]);
        }
    }

    public function test_the_product_page_offers_a_remove_button_for_each_saved_gallery(): void
    {
        $this->get(route('admin.productos.show', $this->plomo))
            ->assertOk()->assertSee('Quitar galería')->assertSee(route('admin.productos.color-images.destroy', $this->plomo), false);
    }

    public function test_removing_a_gallery_deletes_its_files_and_hides_the_color_from_the_store(): void
    {
        $this->get(route('web.products.show', $this->plomo->id))->assertOk()->assertSee('data-pdp-color="PLOMO"', false);

        $this->delete(route('admin.productos.color-images.destroy', $this->plomo), ['color' => 'PLOMO'])
            ->assertRedirect()->assertSessionHas('status');

        $this->assertSame(0, ProductoColorImage::query()->where('color', 'PLOMO')->count());
        Storage::disk('public')->assertMissing('productos/colores/PLOMO.webp');

        // El otro color no se toca y el color sin galería ya no se ofrece en la tienda.
        $this->assertSame(1, ProductoColorImage::query()->where('color', 'AZUL')->count());
        Storage::disk('public')->assertExists('productos/colores/AZUL.webp');
        $page = $this->get(route('web.products.show', $this->plomo->id))->assertOk();
        $page->assertDontSee('data-pdp-color="PLOMO"', false)->assertSee('data-pdp-color="AZUL"', false);
    }

    public function test_a_file_still_used_elsewhere_is_not_deleted(): void
    {
        ProductoColorImage::query()->create(['product_name' => 'OTRO', 'color' => 'PLOMO', 'images' => ['productos/colores/PLOMO.webp']]);

        $this->delete(route('admin.productos.color-images.destroy', $this->plomo), ['color' => 'PLOMO'])->assertRedirect();

        $this->assertSame(0, ProductoColorImage::query()->where(['product_name' => 'CLASICO', 'color' => 'PLOMO'])->count());
        Storage::disk('public')->assertExists('productos/colores/PLOMO.webp');
    }

    public function test_removing_a_color_without_gallery_says_so_and_changes_nothing(): void
    {
        $this->delete(route('admin.productos.color-images.destroy', $this->plomo), ['color' => 'ROJO'])->assertRedirect()->assertSessionHas('error');

        $this->assertSame(2, ProductoColorImage::query()->count());
        $this->delete(route('admin.productos.color-images.destroy', $this->plomo), [])->assertSessionHasErrors('color');
    }

    public function test_after_removing_the_gallery_new_photos_bring_the_color_back(): void
    {
        $this->delete(route('admin.productos.color-images.destroy', $this->plomo), ['color' => 'PLOMO']);
        $this->get(route('web.products.show', $this->plomo->id))->assertDontSee('data-pdp-color="PLOMO"', false);

        ProductoColorImage::query()->create(['product_name' => 'CLASICO', 'color' => 'PLOMO', 'images' => ['productos/colores/nueva.webp']]);
        Producto::refreshCacheVersion();

        $this->get(route('web.products.show', $this->plomo->id))->assertSee('data-pdp-color="PLOMO"', false);
    }
}

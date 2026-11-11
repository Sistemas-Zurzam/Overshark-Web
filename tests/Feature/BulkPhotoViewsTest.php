<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateJwt;
use App\Models\Admin\Producto;
use App\Models\Admin\ProductoColorImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Carga masiva: "01-perla-frente.png" es el color PERLA con la toma "frente"; qué pasa con las fotos anteriores. */
class BulkPhotoViewsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(AuthenticateJwt::class)->actingAs(User::factory()->create());
        Storage::fake('local');
        Storage::fake('public');

        foreach (['PERLA', 'P. ROSA', 'MELANGE O.', 'AZUL', 'NEGRO'] as $color) {
            Producto::query()->create([
                'name' => 'CLASICO', 'default_code' => 'CL-'.$color, 'color' => $color, 'talla' => 'M', 'stock' => 5, 'price' => 17,
                'zazu_company_id' => 1, 'empresa_nombre' => 'OVERSHARK PERU S.A.C',
            ]);
        }
    }

    private function preview(array $names, array $extra = []): array
    {
        $this->post(route('admin.productos.images.preview'), ['product' => 'Clasico'] + $extra + [
            'images' => array_map(fn (string $name) => UploadedFile::fake()->image($name, 800, 1000), $names),
        ])->assertRedirect(route('admin.productos.images.bulk'));

        return session('admin.bulk_product_images.v2');
    }

    public function test_the_photo_view_is_not_part_of_the_color(): void
    {
        $manifest = $this->preview(['01-perla-frente.png', '11-palo-rosa-frente.png', '09-melange-frente.png', '08-azul-frente.png', '12-negro.png']);

        foreach ($manifest['items'] as $item) {
            $this->assertNull($item['error'], $item['filename'].' debería coincidir');
            $this->assertNotNull($item['temp_path']);
        }

        $this->assertSame(['PERLA', 'P. ROSA', 'MELANGE O.', 'AZUL', 'NEGRO'], collect($manifest['items'])->pluck('color')->all());
        $this->assertSame(['frente', 'frente', 'frente', 'frente', null], collect($manifest['items'])->pluck('view')->all());
    }

    public function test_every_known_view_word_is_recognised_and_ordered(): void
    {
        $manifest = $this->preview(['01-azul-frente.png', '01-azul-lateral.png', '01-azul-espalda.png', '01-azul-detalle.png']);

        $this->assertSame([0, 1, 2, 3], collect($manifest['items'])->pluck('view_rank')->all());
        $this->assertSame(['AZUL'], collect($manifest['items'])->pluck('color')->unique()->values()->all());
        $this->assertSame([null, null, null, null], collect($manifest['items'])->pluck('error')->all());
    }

    public function test_front_and_back_with_the_same_number_are_two_photos_of_the_color_and_the_front_goes_first(): void
    {
        // El orden de subida es el contrario al de la galería: la espalda llega primero.
        $manifest = $this->preview(['01-perla-espalda.png', '01-perla-frente.png']);
        $this->assertNull($manifest['items'][0]['error']);
        $this->assertNull($manifest['items'][1]['error']);

        $this->post(route('admin.productos.images.apply'), ['token' => $manifest['token']])->assertRedirect(route('admin.productos.index'));

        $gallery = ProductoColorImage::query()->where(['product_name' => 'CLASICO', 'color' => 'PERLA'])->sole();
        $this->assertCount(2, $gallery->images);
        foreach ($gallery->images as $path) {
            Storage::disk('public')->assertExists($path);
        }
    }

    public function test_two_photos_with_the_same_number_and_view_are_still_rejected(): void
    {
        $manifest = $this->preview(['01-perla-frente.png', '01-perla-frente.jpg']);

        $this->assertStringContainsString('mismo número y la misma toma', (string) $manifest['items'][0]['error']);
        $this->assertNull($manifest['items'][0]['temp_path']);
    }

    public function test_the_preview_says_how_many_current_photos_the_batch_replaces(): void
    {
        ProductoColorImage::query()->create(['product_name' => 'CLASICO', 'color' => 'PERLA', 'images' => ['productos/colores/a.webp', 'productos/colores/b.webp']]);

        $this->preview(['01-perla-frente.png']);

        $this->get(route('admin.productos.images.bulk'))->assertOk()->assertSee('Reemplaza')->assertSee('2 fotos actuales')->assertSee('Toma: frente');
    }

    public function test_applying_replaces_the_color_gallery_and_deletes_the_old_files(): void
    {
        Storage::disk('public')->put('productos/colores/vieja.webp', 'x');
        ProductoColorImage::query()->create(['product_name' => 'CLASICO', 'color' => 'PERLA', 'images' => ['productos/colores/vieja.webp']]);

        $manifest = $this->preview(['01-perla-frente.png']);
        $this->post(route('admin.productos.images.apply'), ['token' => $manifest['token']]);

        $gallery = ProductoColorImage::query()->where(['product_name' => 'CLASICO', 'color' => 'PERLA'])->sole();
        $this->assertCount(1, $gallery->images);
        $this->assertNotContains('productos/colores/vieja.webp', $gallery->images);
        Storage::disk('public')->assertMissing('productos/colores/vieja.webp');
        Storage::disk('public')->assertExists($gallery->images[0]);
    }

    public function test_append_mode_adds_to_the_existing_photos_and_keeps_the_old_files(): void
    {
        Storage::disk('public')->put('productos/colores/frente-vieja.webp', 'x');
        ProductoColorImage::query()->create(['product_name' => 'CLASICO', 'color' => 'PERLA', 'images' => ['productos/colores/frente-vieja.webp']]);

        $manifest = $this->preview(['01-perla-espalda.png'], ['append' => 1]);
        $this->assertTrue($manifest['append']);

        $this->get(route('admin.productos.images.bulk'))->assertSee('Se suma a');
        $this->post(route('admin.productos.images.apply'), ['token' => $manifest['token']]);

        $gallery = ProductoColorImage::query()->where(['product_name' => 'CLASICO', 'color' => 'PERLA'])->sole();
        $this->assertCount(2, $gallery->images);
        $this->assertSame('productos/colores/frente-vieja.webp', $gallery->images[0], 'la anterior queda primero');
        Storage::disk('public')->assertExists('productos/colores/frente-vieja.webp');
    }

    public function test_append_mode_refuses_to_go_over_five_photos(): void
    {
        ProductoColorImage::query()->create(['product_name' => 'CLASICO', 'color' => 'PERLA', 'images' => array_map(fn ($i) => "productos/colores/{$i}.webp", range(1, 5))]);

        $manifest = $this->preview(['01-perla-frente.png'], ['append' => 1]);
        $this->post(route('admin.productos.images.apply'), ['token' => $manifest['token']])->assertRedirect()->assertSessionHas('error');

        $this->assertCount(5, ProductoColorImage::query()->where('color', 'PERLA')->sole()->images);
    }

    public function test_the_product_page_shows_the_whole_photo(): void
    {
        $producto = Producto::query()->where('color', 'PERLA')->firstOrFail();
        $producto->update(['imagen' => 'productos/principal.webp']);

        $html = $this->get(route('admin.productos.show', $producto))->assertOk()->getContent();

        $this->assertStringContainsString('aspect-[4/5]', $html);
        $this->assertStringContainsString('object-contain', $html);
    }
}

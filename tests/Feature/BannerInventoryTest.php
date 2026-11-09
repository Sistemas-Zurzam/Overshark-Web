<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateJwt;
use App\Models\Admin\BannerPortada;
use App\Models\Admin\Combo;
use App\Models\Brand;
use App\Models\PageContent;
use App\Models\User;
use App\Support\SiteContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Admin → Banners como inventario de todo lo visual de la tienda. */
class BannerInventoryTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): static
    {
        return $this->withoutMiddleware(AuthenticateJwt::class)->actingAs(User::factory()->create());
    }

    public function test_the_fixed_images_follow_the_brand_tab_and_the_shared_ones_are_apart(): void
    {
        $page = fn (string $slug) => $this->admin()->get(route('admin.banners.index', ['marca' => Brand::query()->where('slug', $slug)->firstOrFail()->id]))->assertOk();

        $page('overshark')
            ->assertSee('Imágenes fijas · ')
            ->assertSee('Modelo (se ve cuando no hay banners activos)')->assertSee('Modelos de Hombre')->assertSee('Polos')->assertSee('Camisas')
            ->assertSee('Promo Wafflera')->assertSee('Telas')->assertSee('Arma tu outfit (home)')
            ->assertDontSee('Modelos de Mujer')->assertDontSee('Baby tees')->assertDontSee('Boxy Fit')
            ->assertDontSee('Fondo (1440 × 482')
            // Lo compartido va en su propia sección y se ve igual en las tres pestañas.
            ->assertSee('Compartido por toda la tienda')->assertSee('Marca del sitio')->assertSee('Ícono de la pestaña del navegador')
            ->assertSee('Fondo de mar')->assertSee('Compra con confianza')
            ->assertSee('Lo que se cambia en otras pantallas')
            ->assertSee('Combos')->assertSee('Flyer de «Arma tu outfit»')->assertSee('Página Nosotros')->assertSee('Fotos de productos')
            ->assertSee('Logos de las marcas')->assertSee('Medios de pago')
            ->assertSee('Original');

        $page('overshark-girls')
            ->assertSee('Fondo (1440 × 482')->assertSee('Modelos de Mujer')->assertSee('Baby tees')->assertSee('Tops')
            ->assertDontSee('Modelos de Hombre')->assertDontSee('Camisas')->assertDontSee('Boxy Fit')
            ->assertSee('Compartido por toda la tienda');

        $page('bravos')
            ->assertSee('Fondo')->assertSee('Modelo')->assertSee('Boxy Fit')->assertSee('Fotos de la página')
            ->assertDontSee('Promo Wafflera')->assertDontSee('Baby tees')->assertDontSee('Modelos de Hombre')
            ->assertSee('Compartido por toda la tienda');
    }

    public function test_a_brand_without_own_images_says_so_but_keeps_the_shared_section(): void
    {
        $other = Brand::query()->create(['name' => 'Otra', 'slug' => 'otra', 'is_active' => true]);

        $this->admin()->get(route('admin.banners.index', ['marca' => $other->id]))
            ->assertSee('Esta marca no tiene imágenes fijas propias')
            ->assertSee('Compartido por toda la tienda');
    }

    public function test_every_fixed_image_belongs_to_a_known_brand_or_to_the_whole_store(): void
    {
        $slugs = [null, 'overshark', 'overshark-girls', 'bravos'];

        foreach (SiteContent::IMAGES as $key => $meta) {
            $this->assertArrayHasKey(3, $meta, "{$key} no declara su marca");
            $this->assertContains($meta[3], $slugs, "{$key} apunta a una marca desconocida");
        }
    }

    public function test_without_active_banners_it_shows_the_default_hero_the_store_is_using(): void
    {
        $overshark = Brand::query()->where('slug', 'overshark')->firstOrFail();

        $this->admin()->get(route('admin.banners.index', ['marca' => $overshark->id]))
            ->assertSee('Ahora la portada de '.$overshark->name.' muestra la imagen predeterminada');

        BannerPortada::query()->create(['brand_id' => $overshark->id, 'name' => 'Verano', 'image_path' => 'banners/x.webp', 'status' => true, 'time' => 5, 'modo' => 'cover']);

        $this->admin()->get(route('admin.banners.index', ['marca' => $overshark->id]))
            ->assertDontSee('Ahora la portada de '.$overshark->name.' muestra la imagen predeterminada');
    }

    public function test_girls_and_bravos_also_show_their_default_cover_and_a_brand_without_carousel_says_so(): void
    {
        foreach (['overshark-girls', 'bravos'] as $slug) {
            $brand = Brand::query()->where('slug', $slug)->firstOrFail();

            $this->admin()->get(route('admin.banners.index', ['marca' => $brand->id]))
                ->assertSee('Ahora la portada de '.$brand->name.' muestra la imagen predeterminada');
        }

        $other = Brand::query()->create(['name' => 'Otra', 'slug' => 'otra', 'is_active' => true]);
        $this->admin()->get(route('admin.banners.index', ['marca' => $other->id]))->assertSee('Esta marca no tiene portada con carrusel');
    }

    public function test_the_tab_counter_counts_uploaded_banners_and_says_default_when_there_are_none(): void
    {
        $overshark = Brand::query()->where('slug', 'overshark')->firstOrFail();

        $this->admin()->get(route('admin.banners.index'))->assertSee('Predeterminada')->assertSee('Sin banners subidos: la tienda usa su portada predeterminada');

        BannerPortada::query()->create(['brand_id' => $overshark->id, 'name' => 'Uno', 'image_path' => 'banners/uno.webp', 'status' => true, 'time' => 5, 'modo' => 'cover']);
        BannerPortada::query()->create(['brand_id' => $overshark->id, 'name' => 'Dos', 'image_path' => 'banners/dos.webp', 'status' => true, 'time' => 5, 'modo' => 'cover']);

        $this->admin()->get(route('admin.banners.index'))->assertSee('2 banners subidos');
    }

    public function test_the_other_sources_show_real_counts(): void
    {
        Combo::query()->create(['name' => 'Con foto', 'price' => 50, 'status' => true, 'imagen' => 'combos/a.webp', 'items' => []]);
        Combo::query()->create(['name' => 'Sin foto', 'price' => 50, 'status' => true, 'items' => []]);

        $this->admin()->get(route('admin.banners.index'))->assertSee('1 de 2 con imagen propia');
    }

    public function test_an_image_can_be_replaced_from_the_page_and_restored(): void
    {
        Storage::fake('public');
        $original = asset('images/home-hombre/promo.webp');

        $this->admin()->post(route('admin.banners.images', 'promo_hombre'), ['image' => UploadedFile::fake()->image('promo.jpg', 900, 400)])
            ->assertSessionHasNoErrors()->assertRedirect();

        $stored = PageContent::contentFor(SiteContent::SLUG)['images']['promo_hombre'];
        Storage::disk('public')->assertExists($stored);
        $this->assertSame('/storage/'.$stored, SiteContent::asset('images/home-hombre/promo.webp'));

        $this->admin()->get(route('admin.banners.index'))->assertSee('Reemplazada')->assertSee('Restaurar');

        $this->admin()->post(route('admin.banners.images', 'promo_hombre'), ['restore' => 1])->assertRedirect();

        Storage::disk('public')->assertMissing($stored);
        $this->assertSame($original, SiteContent::asset('images/home-hombre/promo.webp'));
    }

    public function test_replacing_the_default_men_hero_shows_on_the_home_when_there_are_no_banners(): void
    {
        Storage::fake('public');

        $this->admin()->post(route('admin.banners.images', 'hero_hombre'), ['image' => UploadedFile::fake()->image('modelo.png', 600, 800)]);

        $stored = PageContent::contentFor(SiteContent::SLUG)['images']['hero_hombre'];
        $this->withSession(['storefront.line' => 'hombre'])->get('/')->assertOk()->assertSee('/storage/'.$stored, false);
    }

    public function test_an_unknown_slot_and_a_non_image_are_rejected(): void
    {
        $this->admin()->post(route('admin.banners.images', 'no-existe'), ['image' => UploadedFile::fake()->image('a.jpg')])->assertNotFound();

        Storage::fake('public');
        $this->admin()->post(route('admin.banners.images', 'promo_hombre'), ['image' => UploadedFile::fake()->create('virus.exe', 10)])
            ->assertSessionHasErrors('image');
        $this->assertArrayNotHasKey('images', PageContent::contentFor(SiteContent::SLUG));
    }
}

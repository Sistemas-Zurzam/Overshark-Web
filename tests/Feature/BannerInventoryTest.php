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

    public function test_the_page_lists_every_fixed_image_group_and_the_other_sources(): void
    {
        $overshark = Brand::query()->where('slug', 'overshark')->firstOrFail();

        $this->admin()->get(route('admin.banners.index', ['marca' => $overshark->id]))
            ->assertOk()
            ->assertSee('Imágenes fijas de la tienda')
            ->assertSee('Portadas')->assertSee('Portada de Hombre · modelo')->assertSee('Portada de Mujer · fondo')
            ->assertSee('Promo Wafflera')->assertSee('Categorías de la home · Hombre')->assertSee('Telas · Mujer')->assertSee('Pantalla de elección')
            ->assertSee('Lo que se cambia en otras pantallas')
            ->assertSee('Combos')->assertSee('Flyer de «Arma tu outfit»')->assertSee('Página Nosotros')->assertSee('Fotos de productos')
            ->assertSee('Logos de las marcas')->assertSee('Logo e ícono de la tienda')->assertSee('Medios de pago')
            ->assertSee('Original');
    }

    public function test_without_active_banners_it_shows_the_default_hero_the_store_is_using(): void
    {
        $overshark = Brand::query()->where('slug', 'overshark')->firstOrFail();

        $this->admin()->get(route('admin.banners.index', ['marca' => $overshark->id]))
            ->assertSee('Ahora la portada de Hombre muestra la imagen predeterminada');

        BannerPortada::query()->create(['brand_id' => $overshark->id, 'name' => 'Verano', 'image_path' => 'banners/x.webp', 'status' => true, 'time' => 5, 'modo' => 'cover']);

        $this->admin()->get(route('admin.banners.index', ['marca' => $overshark->id]))
            ->assertDontSee('Ahora la portada de Hombre muestra la imagen predeterminada');
    }

    public function test_other_brands_explain_that_their_cover_is_a_fixed_image(): void
    {
        $bravos = Brand::query()->where('slug', 'bravos')->firstOrFail();

        $this->admin()->get(route('admin.banners.index', ['marca' => $bravos->id]))
            ->assertSee('todavía no se muestran en la tienda de '.$bravos->name);
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

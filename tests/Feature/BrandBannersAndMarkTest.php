<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateJwt;
use App\Models\Admin\BannerPortada;
use App\Models\Brand;
use App\Models\BrandSetting;
use App\Models\PageContent;
use App\Models\User;
use App\Support\SiteContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Banners de Mujer y Bravos, y la marca del sitio (ícono, logo y WhatsApp) que antes vivía en «Identidad visual». */
class BrandBannersAndMarkTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): static
    {
        return $this->withoutMiddleware(AuthenticateJwt::class)->actingAs(User::factory()->create());
    }

    private function banner(string $slug, array $extra = []): BannerPortada
    {
        $brand = Brand::query()->where('slug', $slug)->firstOrFail();

        return BannerPortada::query()->create($extra + [
            'brand_id' => $brand->id, 'name' => 'Banner '.$slug, 'image_path' => 'banners/'.$slug.'.webp', 'status' => true, 'time' => 5, 'modo' => 'cover',
            'buttons' => [['text' => 'Comprar ya', 'url' => '#productos', 'bg_color' => '#111111', 'text_color' => '#ffffff', 'shape' => 'pill', 'size' => 'md', 'x' => 30, 'y' => 70]],
        ]);
    }

    // ---- Banners de Mujer y Bravos -----------------------------------------------------------------------------

    public function test_the_women_home_shows_the_static_cover_until_girls_has_an_active_banner(): void
    {
        $this->withSession(['storefront.line' => 'mujer'])->get('/')->assertOk()->assertSee('Nacida del arrecife')->assertDontSee('data-carousel="slide"', false);

        $this->banner('overshark-girls');

        $this->withSession(['storefront.line' => 'mujer'])->get('/')
            ->assertOk()
            ->assertSee('data-carousel="slide"', false)
            ->assertSee('/storage/banners/overshark-girls.webp', false)
            ->assertSee('Comprar ya')
            ->assertDontSee('Nacida del arrecife');
    }

    public function test_the_bravos_page_shows_its_banners_instead_of_the_static_cover(): void
    {
        $bravos = Brand::query()->where('slug', 'bravos')->firstOrFail();

        $this->get(route('web.brands.show', $bravos))->assertOk()->assertDontSee('data-carousel="slide"', false);

        $this->banner('bravos');
        $this->banner('bravos', ['name' => 'Segundo', 'image_path' => 'banners/bravos-2.webp']);

        $this->get(route('web.brands.show', $bravos))
            ->assertOk()
            ->assertSee('data-carousel="slide"', false)
            ->assertSee('/storage/banners/bravos.webp', false)
            ->assertSee('/storage/banners/bravos-2.webp', false)
            ->assertSee('Mostrar banner 2');
    }

    public function test_banners_of_one_brand_never_show_on_another_brands_cover(): void
    {
        $this->banner('bravos');

        $this->withSession(['storefront.line' => 'mujer'])->get('/')->assertDontSee('/storage/banners/bravos.webp', false);
        $this->withSession(['storefront.line' => 'hombre'])->get('/')->assertDontSee('/storage/banners/bravos.webp', false);
    }

    public function test_a_banner_change_shows_right_away_and_not_after_the_cache_expires(): void
    {
        $banner = $this->banner('bravos');
        $bravos = Brand::query()->where('slug', 'bravos')->firstOrFail();

        $this->get(route('web.brands.show', $bravos))->assertSee('/storage/banners/bravos.webp', false);

        // Antes de la corrección, apagar el banner no se notaba hasta que vencía la memoria de 30 minutos.
        $banner->update(['status' => false]);
        $this->get(route('web.brands.show', $bravos))->assertDontSee('/storage/banners/bravos.webp', false);

        $second = $this->banner('bravos', ['name' => 'Otro', 'image_path' => 'banners/otro.webp']);
        $this->get(route('web.brands.show', $bravos))->assertSee('/storage/banners/otro.webp', false);

        $second->delete();
        $this->get(route('web.brands.show', $bravos))->assertDontSee('/storage/banners/otro.webp', false);
    }

    public function test_reordering_banners_is_reflected_on_the_cover_at_once(): void
    {
        $first = $this->banner('bravos', ['sort_order' => 1, 'image_path' => 'banners/uno.webp']);
        $second = $this->banner('bravos', ['sort_order' => 2, 'image_path' => 'banners/dos.webp']);
        $bravos = Brand::query()->where('slug', 'bravos')->firstOrFail();

        $this->get(route('web.brands.show', $bravos))->assertSeeInOrder(['uno.webp', 'dos.webp'], false);

        $this->admin()->post(route('admin.banners.reorder'), ['ids' => [$second->id, $first->id]]);

        $this->get(route('web.brands.show', $bravos))->assertSeeInOrder(['dos.webp', 'uno.webp'], false);
    }

    // ---- Marca del sitio: ya no existe «Identidad visual» -------------------------------------------------------

    public function test_the_identity_screen_and_its_table_are_gone_and_the_old_url_redirects(): void
    {
        $this->assertFalse(Schema::hasTable('brand_settings'));

        $this->admin()->get('/admin/identidad-visual')->assertRedirect('/admin/contenido-tienda');
        $this->admin()->get(route('admin.dashboard'))->assertOk()->assertDontSee('Identidad visual');
    }

    public function test_whatsapp_is_edited_in_the_store_content_and_drives_the_floating_button_and_the_links(): void
    {
        $brand = BrandSetting::current();
        $this->assertNull($brand->whatsappUrl());

        $this->admin()->put(route('admin.contenido.update'), ['c' => ['contacto' => ['whatsapp' => '957 249 042']]])->assertSessionHasNoErrors();

        $this->assertSame('https://wa.me/51957249042?text='.rawurlencode(BrandSetting::WHATSAPP_DEFAULT_MESSAGE), BrandSetting::current()->whatsappUrl());
        $this->assertSame('+51 957 249 042', BrandSetting::current()->whatsappDisplay());
        $this->get(route('web.contact'))->assertSee('+51 957 249 042');

        $this->admin()->get(route('admin.contenido.edit'))->assertSee('Número de WhatsApp')->assertSee('value="957 249 042"', false);
    }

    public function test_the_site_icon_and_logo_are_replaced_from_banners_including_svg_and_ico(): void
    {
        Storage::fake('public');

        $this->assertNull(SiteContent::imageUrl('site_icon'));
        $this->assertStringContainsString('logo-overshark.png', (string) BrandSetting::current()->logoUrl());

        $this->admin()->post(route('admin.banners.images', 'site_icon'), ['image' => UploadedFile::fake()->create('favicon.ico', 4, 'image/x-icon')])->assertSessionHasNoErrors();
        $this->admin()->post(route('admin.banners.images', 'site_logo'), ['image' => UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"><rect width="10" height="10"/></svg>')])->assertSessionHasNoErrors();

        $icon = PageContent::contentFor(SiteContent::SLUG)['images']['site_icon'];
        Storage::disk('public')->assertExists($icon);
        $this->assertSame('/storage/'.$icon, BrandSetting::current()->iconUrl());
        $this->assertStringContainsString('/storage/', (string) BrandSetting::current()->logoUrl());

        // El favicon sale en la tienda y en el panel.
        $this->get(route('web.contact'))->assertSee('<link rel="icon" href="/storage/'.$icon.'">', false);
        $this->admin()->get(route('admin.dashboard'))->assertSee('<link rel="icon" href="/storage/'.$icon.'">', false);
    }

    public function test_the_other_fixed_images_still_reject_svg(): void
    {
        Storage::fake('public');

        $this->admin()->post(route('admin.banners.images', 'promo_hombre'), [
            'image' => UploadedFile::fake()->createWithContent('promo.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>'),
        ])->assertSessionHasErrors('image');
    }

    public function test_the_migration_copies_the_old_whatsapp_and_images_into_the_store_content(): void
    {
        // Se reconstruye la tabla vieja con datos y se corre la migración de nuevo.
        $migration = require base_path('database/migrations/2026_11_09_000500_move_brand_settings_into_site_content.php');
        $migration->down();
        \Illuminate\Support\Facades\DB::table('brand_settings')->insert([
            'logo_path' => 'branding/logo-viejo.png', 'icon_path' => 'branding/icono-viejo.png',
            'whatsapp_number' => '999111222', 'whatsapp_message' => 'Hola desde antes', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $migration->up();

        $this->assertFalse(Schema::hasTable('brand_settings'));
        $content = PageContent::query()->where('slug', 'tienda')->firstOrFail()->content;
        $this->assertSame('branding/logo-viejo.png', $content['images']['site_logo']);
        $this->assertSame('branding/icono-viejo.png', $content['images']['site_icon']);
        $this->assertSame('999111222', $content['contacto']['whatsapp']);
        $this->assertSame('Hola desde antes', $content['contacto']['whatsapp_message']);
        $this->assertSame('https://wa.me/51999111222?text='.rawurlencode('Hola desde antes'), BrandSetting::current()->whatsappUrl());
    }
}

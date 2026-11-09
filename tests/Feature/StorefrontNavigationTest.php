<?php

namespace Tests\Feature;

use App\Models\Admin\Categoria;
use App\Models\Admin\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontNavigationTest extends TestCase
{
    use \Tests\Concerns\GivesProductsColorPhotos;
    use RefreshDatabase;

    public function test_header_links_to_real_categories_and_not_to_admin(): void
    {
        $this->withSession(['storefront.line' => 'hombre'])
            ->get('/')
            ->assertOk()
            ->assertSee(route('web.products.search', ['orden' => 'populares']), false)
            ->assertSee(route('web.outfit'), false)
            ->assertSeeText('Nosotros')
            ->assertSeeText('Contacto')
            ->assertSee(route('web.home', ['linea' => 'mujer']), false)
            ->assertSee('href="'.route('login').'"', false)
            ->assertSee('aria-label="Iniciar sesión"', false)
            ->assertDontSee(route('admin.dashboard'), false);
    }

    public function test_men_home_lists_best_sellers_without_girls_products_and_the_design_sections(): void
    {
        $this->product('WAFFLE', ['talla' => 'M', 'color' => 'PERLA', 'stock' => 50]);
        $this->product('BABY TY', ['talla' => 'S', 'color' => 'ROSA', 'stock' => 90]);

        $this->withSession(['storefront.line' => 'hombre'])
            ->get('/')
            ->assertOk()
            ->assertSee('Nacido del océano')
            ->assertSeeInOrder(['Los más vendidos', 'Waffle · Perla', '9 prendas × S/ 99', 'Categorías', 'Por qué elegir Overshark', 'No destiñe', 'Waffle', '¡Arma tu outfit!', 'Compra con confianza', 'Otras tiendas'], false)
            ->assertDontSee('Baby Ty');
    }

    public function test_floating_whatsapp_button_shows_only_with_a_number_set_in_admin(): void
    {
        $this->get(route('web.nosotros'))->assertOk()->assertDontSee('os-wa', false);

        $this->withoutMiddleware(\App\Http\Middleware\AuthenticateJwt::class);
        $this->actingAs(\App\Models\User::factory()->create());

        $this->put(route('admin.brand.whatsapp'), ['whatsapp_number' => '999 888 777', 'whatsapp_message' => 'Hola, quiero un combo'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->get(route('web.nosotros'))
            ->assertOk()
            ->assertSee('https://wa.me/51999888777?text=Hola%2C%20quiero%20un%20combo', false);

        $this->put(route('admin.brand.whatsapp'), ['whatsapp_number' => 'abc'])->assertSessionHasErrors('whatsapp_number');
    }

    public function test_about_page_tells_the_manufacturing_story_and_is_linked_from_the_menu(): void
    {
        $this->get(route('web.nosotros'))
            ->assertOk()
            ->assertSeeInOrder(['Somos Overshark', 'Básicos que se sienten como un triunfo', 'Nuestro ADN', 'Así creamos lo que vistes.', 'Una familia, tres marcas', 'Forma parte de la comunidad'])
            ->assertSeeInOrder(['Tejido', 'Teñido', 'Corte', 'Confección', 'Acabados y calidad', 'Empaque'])
            ->assertSee(route('web.home', ['linea' => 'mujer']), false)
            ->assertSee('<meta name="description" content="Overshark fabrica sus prendas', false)
            ->assertSee('aria-current="page" >Nosotros', false)
            // Lo pendiente de confirmar no se muestra hasta completarlo en el admin.
            ->assertDontSee('Desde')
            ->assertDontSee('INSTAGRAM')
            ->assertDontSee('Foto pendiente');
    }

    public function test_admin_edits_about_page_texts_and_photos(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $this->withoutMiddleware(\App\Http\Middleware\AuthenticateJwt::class);
        $this->actingAs(\App\Models\User::factory()->create());

        $this->get(route('admin.about.edit'))->assertOk()
            ->assertSeeInOrder(['1. Portada', '2. Nuestra historia', '3. Nuestro ADN', '4. Proceso de fabricación', '5. Datos clave', '6. Nuestras marcas', '7. Comunidad']);

        $content = \App\Support\AboutPage::defaults();
        $content['hero']['title'] = 'Tejemos tu estilo';
        $content['historia']['year'] = '2019';
        $content['comunidad']['handle'] = '@overshark.pe';
        $content['comunidad']['instagram'] = 'https://instagram.com/overshark.pe';
        $content['proceso']['steps'][0]['title'] = 'Hilado';
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');

        $this->put(route('admin.about.update'), [
            ...$content,
            'images' => ['paso_1' => \Illuminate\Http\UploadedFile::fake()->createWithContent('tejido.png', $png)],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $stored = \App\Models\PageContent::query()->where('slug', 'nosotros')->firstOrFail()->content;
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($stored['images']['paso_1']);

        $this->get(route('web.nosotros'))
            ->assertOk()
            ->assertSee('Tejemos tu estilo')
            ->assertSee('Desde')
            ->assertSee('2019')
            ->assertSee('Etiquétanos con @overshark.pe')
            ->assertSee('https://instagram.com/overshark.pe', false)
            ->assertSee('Hilado')
            ->assertSee('/storage/'.$stored['images']['paso_1'], false);

        $this->put(route('admin.about.update'), [...$content, 'comunidad' => [...$content['comunidad'], 'instagram' => 'no es un enlace']])
            ->assertSessionHasErrors('comunidad.instagram');

        $this->post(route('admin.about.reset'))->assertRedirect();
        $this->get(route('web.nosotros'))->assertSee('Somos Overshark')->assertDontSee('Hilado')->assertDontSee('2019');
    }

    public function test_contact_form_saves_the_message_for_the_admin(): void
    {
        $this->get(route('web.contact'))->assertOk()->assertSee('Enviar mensaje');

        $this->post(route('web.contact.store'), [])->assertSessionHasErrors(['name', 'email', 'prefers_contact', 'subject', 'message', 'privacy']);

        $this->post(route('web.contact.store'), [
            'name' => 'Ana Pérez', 'email' => 'ana@example.com', 'phone' => '999 888 777',
            'prefers_contact' => 'whatsapp', 'subject' => 'producto', 'message' => '¿Tienen el Waffle en talla XL?', 'privacy' => 1,
        ])->assertRedirect(route('web.contact'))->assertSessionHas('contact_sent');

        $this->assertDatabaseHas('contact_messages', ['email' => 'ana@example.com', 'subject' => 'producto', 'read_at' => null]);

        // Campo trampa lleno (bot): no se guarda nada.
        $this->post(route('web.contact.store'), ['website' => 'spam', 'name' => 'x', 'email' => 'bot@example.com'])->assertRedirect();
        $this->assertDatabaseMissing('contact_messages', ['email' => 'bot@example.com']);

        $this->withoutMiddleware(\App\Http\Middleware\AuthenticateJwt::class);
        $this->actingAs(\App\Models\User::factory()->create());
        $this->get(route('admin.messages.index'))->assertOk()->assertSee('Ana Pérez')->assertSee('Pendientes');
    }

    public function test_category_filter_matches_product_name_or_synced_category(): void
    {
        $bottoms = Categoria::query()->create(['name' => 'Pantalones']);

        $this->product('POLO PIQUE CLASICO');
        $this->product('JOGGER URBANO');
        $this->product('CARGO NEGRO', ['categoria_id' => $bottoms->id]);

        $this->get(route('web.products.search', ['categoria' => 'pantalones']))
            ->assertOk()
            ->assertSee('Jogger Urbano')
            ->assertSee('Cargo Negro')
            ->assertDontSee('Polo Pique Clasico');
    }

    public function test_shop_filters_by_size_color_and_price_and_paginates_by_twelve(): void
    {
        $this->product('POLO WAFFLE', ['talla' => 'M', 'color' => 'AZUL', 'price' => 55]);
        $this->product('POLO WAFFLE', ['talla' => 'L', 'color' => 'NEGRO', 'price' => 55, 'default_code' => 'PW-L']);
        $this->product('CAMISA LINO', ['talla' => 'L', 'color' => 'AZUL', 'price' => 80]);
        $this->product('POLERA NERU', ['talla' => 'S', 'color' => 'VINO', 'price' => 65]);

        $this->get(route('web.products.search'))
            ->assertOk()
            ->assertSee('3 productos')
            ->assertSeeInOrder(['Polos', '1', 'Camisas', '1', 'Poleras', '1']);

        $this->get(route('web.products.search', ['talla' => 'M']))
            ->assertSee('Polo Waffle · Azul')
            ->assertDontSee('Camisa Lino')
            ->assertSee('Talla M');

        $this->get(route('web.products.search', ['color' => 'negro']))
            ->assertSee('Polo Waffle · Negro')
            ->assertDontSee('Camisa Lino');

        $this->get(route('web.products.search', ['max' => 60]))
            ->assertSee('Polo Waffle')
            ->assertDontSee('Camisa Lino')
            ->assertDontSee('Polera Neru');

        $this->get(route('web.products.search', ['orden' => 'precio-desc']))
            ->assertSeeInOrder(['Camisa Lino', 'Polera Neru', 'Polo Waffle']);

        foreach (range(1, 12) as $i) {
            $this->product("POLO EXTRA {$i}");
        }

        $this->get(route('web.products.search'))->assertSee('Mostrando 1–12 de 15')->assertSee('aria-label="Página siguiente"', false);
        $this->get(route('web.products.search', ['page' => 2]))->assertSee('Mostrando 13–15 de 15');
    }

    public function test_women_shop_uses_the_girls_categories_and_design(): void
    {
        $this->product('BABY TY BASICA', ['talla' => 'S', 'color' => 'LILA']);
        $this->product('TOP RIB CROPPED', ['talla' => 'M', 'color' => 'ROSA']);
        $this->product('BUZO JOGGER', ['talla' => 'M', 'color' => 'ROSA']);
        $this->product('POLO WAFFLE');

        $this->get(route('web.products.search', ['linea' => 'mujer']))
            ->assertOk()
            ->assertSessionHas('storefront.line', 'mujer')
            ->assertSee('os-hh--girls', false)
            ->assertSee('3 productos')
            ->assertSeeInOrder(['Baby tees', '1', 'Tops', '1', 'Poleras', '0', 'Joggers', '1', 'Sets', '0'])
            ->assertSeeText('9 prendas × S/ 99')
            ->assertDontSee('Polo Waffle');

        $this->get(route('web.products.search', ['categoria' => 'joggers']))
            ->assertSee('Buzo Jogger · Rosa')
            ->assertDontSee('Baby Ty Basica');

        // ?linea=hombre vuelve a la tienda de hombre.
        $this->get(route('web.products.search', ['linea' => 'hombre']))
            ->assertSessionHas('storefront.line', 'hombre')
            ->assertDontSee('os-hh--girls', false)
            ->assertSee('Polo Waffle');
    }

    public function test_news_lists_the_latest_products_first(): void
    {
        $this->product('POLO ANTIGUO', ['created_at' => now()->subMonth()]);
        $this->product('POLO RECIENTE', ['created_at' => now()]);

        $this->get(route('web.products.search', ['orden' => 'novedades']))
            ->assertOk()
            ->assertSee('Novedades')
            ->assertSeeInOrder(['Polo Reciente', 'Polo Antiguo']);
    }

    private function product(string $name, array $attributes = []): Producto
    {
        $product = Producto::query()->create([
            'name' => $name,
            'default_code' => str_replace(' ', '-', $name),
            'stock' => 5,
            'price' => 39.9,
            'zazu_company_id' => 1,
            ...array_diff_key($attributes, ['created_at' => true]),
        ]);

        if (isset($attributes['created_at'])) {
            $product->forceFill(['created_at' => $attributes['created_at']])->saveQuietly();
        }

        return $product;
    }
}

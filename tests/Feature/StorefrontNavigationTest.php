<?php

namespace Tests\Feature;

use App\Models\Admin\Categoria;
use App\Models\Admin\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontNavigationTest extends TestCase
{
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
            ->assertSee('Mi cuenta · Próximamente')
            ->assertDontSee(route('admin.dashboard'), false);
    }

    public function test_about_page_tells_the_manufacturing_story_and_is_linked_from_the_menu(): void
    {
        $this->get(route('web.nosotros'))
            ->assertOk()
            ->assertSee('Creamos tu estilo desde el', false)
            ->assertSee('Así creamos lo que vistes.')
            ->assertSeeInOrder(['Tejido', 'Teñido', 'Corte', 'Confección', 'Acabados y calidad', 'Empaque'])
            ->assertSee(route('web.outfit'), false)
            ->assertSee('<meta name="description" content="Overshark fabrica sus prendas', false)
            ->assertSee('aria-current="page" >Nosotros', false);
    }

    public function test_admin_edits_about_page_texts_and_photos(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $this->withoutMiddleware(\App\Http\Middleware\AuthenticateJwt::class);
        $this->actingAs(\App\Models\User::factory()->create());

        $content = \App\Support\AboutPage::defaults();
        $content['hero']['title'] = 'Tejemos tu estilo';
        $content['hero']['highlight'] = 'en casa.';
        $content['proceso']['steps'][0]['title'] = 'Hilado';
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');

        $this->put(route('admin.about.update'), [
            ...\Illuminate\Support\Arr::except($content, ['conectada']),
            'conectada' => [...$content['conectada'], 'show_products' => 0],
            'images' => ['paso_1' => \Illuminate\Http\UploadedFile::fake()->createWithContent('tejido.png', $png)],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $stored = \App\Models\PageContent::query()->where('slug', 'nosotros')->firstOrFail()->content;
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($stored['images']['paso_1']);

        $this->get(route('web.nosotros'))
            ->assertOk()
            ->assertSee('Tejemos tu estilo')
            ->assertSee('en casa.')
            ->assertSee('Hilado')
            ->assertSee('/storage/'.$stored['images']['paso_1'], false);

        $this->post(route('admin.about.reset'))->assertRedirect();
        $this->get(route('web.nosotros'))->assertSee('Creamos tu estilo desde el')->assertDontSee('Hilado');
    }

    public function test_contact_form_saves_the_message_for_the_admin(): void
    {
        $this->get(route('web.contact'))->assertOk()->assertSee('Enviar mensaje');

        $this->post(route('web.contact.store'), [])->assertSessionHasErrors(['name', 'email', 'subject', 'message', 'privacy']);

        $this->post(route('web.contact.store'), [
            'name' => 'Ana Pérez', 'email' => 'ana@example.com', 'phone' => '999 888 777',
            'subject' => 'producto', 'message' => '¿Tienen el Waffle en talla XL?', 'privacy' => 1,
        ])->assertRedirect(route('web.contact'))->assertSessionHas('contact_sent');

        $this->assertDatabaseHas('contact_messages', ['email' => 'ana@example.com', 'subject' => 'producto', 'read_at' => null]);

        // Campo trampa lleno (bot): no se guarda nada.
        $this->post(route('web.contact.store'), ['website' => 'spam', 'name' => 'x', 'email' => 'bot@example.com'])->assertRedirect();
        $this->assertDatabaseMissing('contact_messages', ['email' => 'bot@example.com']);

        $this->withoutMiddleware(\App\Http\Middleware\AuthenticateJwt::class);
        $this->actingAs(\App\Models\User::factory()->create());
        $this->get(route('admin.messages.index'))->assertOk()->assertSee('Ana Pérez')->assertSee('Pendientes (1)');
    }

    public function test_category_filter_matches_product_name_or_synced_category(): void
    {
        $bottoms = Categoria::query()->create(['name' => 'Pantalones']);

        $this->product('POLO PIQUE CLASICO');
        $this->product('JOGGER URBANO');
        $this->product('CARGO NEGRO', ['categoria_id' => $bottoms->id]);

        $this->get(route('web.products.search', ['categoria' => 'pantalones']))
            ->assertOk()
            ->assertSee('JOGGER URBANO')
            ->assertSee('CARGO NEGRO')
            ->assertDontSee('POLO PIQUE CLASICO');
    }

    public function test_news_lists_the_latest_products_first(): void
    {
        $this->product('POLO ANTIGUO', ['created_at' => now()->subMonth()]);
        $this->product('POLO RECIENTE', ['created_at' => now()]);

        $this->get(route('web.products.search', ['orden' => 'novedades']))
            ->assertOk()
            ->assertSee('Novedades')
            ->assertSeeInOrder(['POLO RECIENTE', 'POLO ANTIGUO']);
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

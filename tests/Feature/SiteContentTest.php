<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateJwt;
use App\Models\Admin\Producto;
use App\Models\PageContent;
use App\Models\User;
use App\Support\Checkout;
use App\Support\LegalDocuments;
use App\Support\Shipping;
use App\Support\SiteContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SiteContentTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): static
    {
        return $this->withoutMiddleware(AuthenticateJwt::class)->actingAs(User::factory()->create());
    }

    private function save(array $content, array $extra = [])
    {
        return $this->admin()->put(route('admin.contenido.update'), ['c' => $content] + $extra);
    }

    public function test_without_edits_the_store_shows_the_original_texts_and_prices(): void
    {
        $this->assertSame(15.0, Shipping::rate('domicilio'));
        $this->assertSame(14.0, Shipping::rate('agencia'));
        $this->assertSame(30.0, Checkout::depositFor('agencia'));

        $this->get(route('web.contact'))
            ->assertOk()
            ->assertSee('ventas@overshark.com')
            ->assertSee('9am a 6pm L-V')
            ->assertSee('Lima: delivery S/ 15 · Agencia Shalom S/ 14')
            ->assertSee('¿Cuánto cuesta el envío?')
            ->assertSee('cuesta S/ 15')
            ->assertSee('Combos 3 × S/ 50');
    }

    public function test_admin_prices_change_what_is_charged_and_what_every_page_says(): void
    {
        $this->save(['envio' => ['home' => '18', 'agency' => '12', 'deposit_home' => '20', 'deposit_agency' => '25']])
            ->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame(18.0, Shipping::rate('domicilio'));
        $this->assertSame(12.0, Shipping::rate('agencia'));
        $this->assertSame(12.0, Shipping::cheapest());
        $this->assertSame(25.0, Checkout::depositFor('agencia'));
        $this->assertSame(20.0, Checkout::depositFor('domicilio'));

        $this->get(route('web.contact'))
            ->assertSee('Lima: delivery S/ 18 · Agencia Shalom S/ 12')
            ->assertSee('cuesta S/ 18')
            ->assertDontSee('Shalom S/ 14');

        $polo = Producto::query()->create(['name' => 'CLASICO', 'color' => null, 'talla' => 'M', 'stock' => 5, 'price' => 40, 'zazu_company_id' => 1]);
        $this->get(route('web.products.show', $polo->id))->assertSee('Envío a todo el Perú desde S/ 12');
    }

    public function test_free_shipping_is_a_valid_price_and_an_empty_field_goes_back_to_the_default(): void
    {
        $this->save(['envio' => ['home' => '0', 'agency' => '9']]);
        $this->assertSame(0.0, Shipping::rate('domicilio'));
        $this->assertSame(9.0, Shipping::cheapest(), 'el envío gratis no cuenta como el más barato');

        $this->save(['envio' => ['home' => '', 'agency' => '']]);
        $this->assertSame(15.0, Shipping::rate('domicilio'));
        $this->assertSame(14.0, Shipping::rate('agencia'));
    }

    public function test_only_what_differs_from_the_default_is_stored(): void
    {
        $this->save(['envio' => ['home' => '15', 'agency' => '11'], 'contacto' => ['email' => 'ventas@overshark.com', 'horario' => '10am a 7pm']]);

        $saved = PageContent::contentFor(SiteContent::SLUG);
        $this->assertArrayNotHasKey('home', $saved['envio']);
        $this->assertEquals(11, $saved['envio']['agency']);
        $this->assertArrayNotHasKey('email', $saved['contacto'] ?? []);
        $this->assertSame('10am a 7pm', $saved['contacto']['horario']);
    }

    public function test_contact_data_and_faqs_are_editable_with_price_marks(): void
    {
        $this->save([
            'contacto' => [
                'email' => 'hola@overshark.pe',
                'horario' => 'Todos los días 9am a 9pm',
                'faq' => [
                    ['q' => '¿Hacen envíos?', 'a' => 'Sí, desde {desde}.'],
                    ['q' => '', 'a' => ''],
                ],
            ],
        ])->assertSessionHasNoErrors();

        $this->get(route('web.contact'))
            ->assertSee('hola@overshark.pe')
            ->assertSee('Todos los días 9am a 9pm')
            ->assertSee('¿Hacen envíos?')
            ->assertSee('Sí, desde S/ 14.')
            ->assertDontSee('¿Cómo funcionan los combos?');

        $this->get(route('web.home', ['linea' => 'hombre']))->assertSee('hola@overshark.pe');
    }

    public function test_a_question_without_answer_is_rejected(): void
    {
        $this->save(['contacto' => ['faq' => [['q' => '¿Y esto?', 'a' => '']]]])->assertSessionHasErrors('c.contacto.faq.0.a');
    }

    public function test_provider_data_shows_in_the_legal_pages_and_the_claims_book(): void
    {
        $this->get(route('web.terms'))->assertSee('[por confirmar]');

        $this->save(['proveedor' => ['razon_social' => 'Overshark SAC', 'ruc' => '20123456789', 'domicilio' => 'Av. Lima 123', 'canal' => 'reclamos@overshark.pe']]);

        foreach ([route('web.terms'), route('web.privacy'), route('web.claims.create')] as $url) {
            $this->get($url)->assertSee('Overshark SAC')->assertSee('20123456789')->assertSee('reclamos@overshark.pe')->assertDontSee('[por confirmar]');
        }
    }

    public function test_legal_texts_are_editable_and_the_draft_note_can_be_hidden(): void
    {
        $this->get(route('web.privacy'))->assertSee('Versión de trabajo');

        $this->save(['legal' => [
            'privacy' => [0 => ['h' => '1. Quién es el responsable', 't' => 'Overshark SAC trata tus datos.']],
            'hide_note' => '1',
        ]]);

        $this->assertSame('1. Quién es el responsable', LegalDocuments::find('privacy')['sections'][0][0]);
        $this->assertSame('2. Datos que solicitamos', LegalDocuments::find('privacy')['sections'][1][0], 'las demás secciones siguen igual');

        $this->get(route('web.privacy'))
            ->assertSee('1. Quién es el responsable')
            ->assertSee('Overshark SAC trata tus datos.')
            ->assertDontSee('Versión de trabajo');
        $this->get(route('web.contact'))->assertSee('Overshark SAC trata tus datos.', false);
    }

    public function test_top_bar_and_promo_texts_follow_the_price_and_the_content(): void
    {
        $this->save(['promos' => ['combo' => '3 × S/ 55', 'anuncio_pago' => 'Paga al recibir', 'wafflera_title' => '10 prendas × S/ 120']]);

        $this->withSession(['storefront.line' => 'hombre'])->get('/')
            ->assertOk()
            ->assertSee('Combos 3 × S/ 55')
            ->assertSee('Paga al recibir')
            ->assertSee('Envío desde S/ 14 a todo el Perú')
            ->assertSee('10 prendas × S/ 120')
            ->assertDontSee('3 × S/ 50');
    }

    public function test_home_hero_texts_and_the_women_hero_photo_are_editable(): void
    {
        Storage::fake('public');

        $this->save(
            ['hero' => ['hombre' => ['title' => 'Salvaje por naturaleza'], 'mujer' => ['accent' => 'Hecha para brillar más']]],
            ['images' => ['hero_mujer' => UploadedFile::fake()->image('hero.jpg', 1440, 482)]],
        )->assertSessionHasNoErrors();

        $this->withSession(['storefront.line' => 'hombre'])->get('/')->assertSee('Salvaje por naturaleza')->assertDontSee('Nacido del océano');

        $uploaded = PageContent::contentFor(SiteContent::SLUG)['images']['hero_mujer'];
        Storage::disk('public')->assertExists($uploaded);

        $this->withSession(['storefront.line' => 'mujer'])->get('/')
            ->assertSee('Hecha para brillar más')
            ->assertSee('/storage/'.$uploaded, false);

        $this->save([], ['remove_images' => ['hero_mujer' => '1']]);
        Storage::disk('public')->assertMissing($uploaded);
    }

    public function test_prices_and_emails_are_validated(): void
    {
        $this->save(['envio' => ['home' => '-3'], 'contacto' => ['email' => 'no-es-correo']])
            ->assertSessionHasErrors(['c.envio.home', 'c.contacto.email']);
    }

    public function test_reset_restores_every_text_and_price_but_keeps_the_photos(): void
    {
        Storage::fake('public');
        $this->save(['envio' => ['home' => '20'], 'contacto' => ['horario' => 'Siempre']], ['images' => ['hero_mujer' => UploadedFile::fake()->image('hero.jpg')]]);

        $this->admin()->post(route('admin.contenido.reset'))->assertRedirect();

        $this->assertSame(15.0, Shipping::rate('domicilio'));
        $this->assertSame('9am a 6pm L-V', SiteContent::get('contacto.horario'));
        $this->assertNotEmpty(PageContent::contentFor(SiteContent::SLUG)['images']['hero_mujer']);
    }

    public function test_the_admin_page_renders_with_current_values(): void
    {
        $this->save(['envio' => ['home' => '17.5']]);

        $this->admin()->get(route('admin.contenido.edit'))
            ->assertOk()
            ->assertSee('Contenido de la tienda')
            ->assertSee('value="17.5"', false)
            ->assertSee('Términos y condiciones (documento legal)')
            ->assertSee('Pregunta 1');
    }

    public function test_the_wafflera_banner_links_to_combos_and_can_show_only_the_uploaded_image(): void
    {
        // Por defecto: texto y botón sobre la imagen, y todo el banner (no solo el botón) lleva a Combos.
        $this->withSession(['storefront.line' => 'hombre'])->get('/')->assertOk()
            ->assertSee('9 prendas × S/ 99')
            ->assertSee('Arma tu combo →')
            ->assertSee('after:absolute after:inset-0', false);

        // Con «solo imagen» la imagen subida ya trae su texto: no se encima nada y el banner entero es un enlace.
        $this->save(['promos' => ['wafflera_image_only' => '1']])->assertRedirect();
        $this->assertTrue((bool) SiteContent::get('promos.wafflera_image_only'));
        $this->withSession(['storefront.line' => 'hombre'])->get('/')->assertOk()
            ->assertDontSee('Arma tu combo →')
            ->assertDontSee('Elige color y talla de cada prenda')
            ->assertSee('href="'.route('web.combos.index').'" class="os-focus absolute inset-0"', false);

        // Apagarlo vuelve a mostrar el texto.
        $this->save(['promos' => ['wafflera_image_only' => '0']])->assertRedirect();
        $this->assertFalse((bool) SiteContent::get('promos.wafflera_image_only'));
        $this->withSession(['storefront.line' => 'hombre'])->get('/')->assertSee('Arma tu combo →');

        $this->admin()->get(route('admin.contenido.edit'))->assertOk()->assertSee('Promo Wafflera · mostrar solo la imagen');
    }
}

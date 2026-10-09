<?php

namespace Tests\Feature;

use App\Models\Admin\Producto;
use App\Services\OutfitAi\OutfitAiGenerator;
use App\Services\OutfitAi\OutfitAiResult;
use App\Services\OutfitAi\UnconfiguredOutfitAiGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OutfitBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_page_shows_real_products_for_the_selected_line(): void
    {
        $this->variant('POLO PIQUE CLASICO', 'M', 'Negro', 49.9, 4);
        $this->variant('CAMISERO PIKE', 'L', 'Azul', 0, 3);
        $this->variant('AIR PODS PRO MAX', 'UNICA', 'Negro', 0, 9);

        $this->withSession(['storefront.line' => 'mujer'])
            ->get(route('web.outfit.show'))
            ->assertOk()
            ->assertSee('Productos para mujer')
            ->assertSee('Volver a mujer')
            ->assertSee(route('web.home', ['linea' => 'mujer']), false)
            ->assertSee('Polo Pique Clasico')
            ->assertSee('S/ 49.90')
            ->assertSee('Camisero Pike')
            ->assertSee('Precio por<br>confirmar', false)
            ->assertDontSee('Air Pods Pro Max')
            ->assertSee('En tu dispositivo')
            ->assertSee('No se envía a ningún servicio externo.');
    }

    public function test_photo_is_stored_privately_and_only_served_to_its_session(): void
    {
        $response = $this->post(route('web.outfit.photo.store'), [
            'photo' => UploadedFile::fake()->image('yo.png', 600, 800),
        ], ['Accept' => 'application/json']);

        $response->assertOk()->assertJsonPath('ai_available', false);
        $path = session('outfit.photo');

        $this->assertMatchesRegularExpression('#^outfit/photos/[A-Za-z0-9]{40}\.jpg$#', $path);
        Storage::disk('local')->assertExists($path);
        $this->assertStringNotContainsString($path, $response->getContent());

        $this->get(route('web.outfit.photo.show'))
            ->assertOk()
            ->assertHeader('Cache-Control', 'max-age=0, no-store, private');

        $this->flushSession();

        $this->get(route('web.outfit.photo.show'))->assertNotFound();
    }

    public function test_photo_upload_rejects_invalid_files(): void
    {
        $cases = [
            'svg' => UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>'),
            'pdf' => UploadedFile::fake()->create('doc.pdf', 20, 'application/pdf'),
            'too small' => UploadedFile::fake()->image('tiny.png', 100, 100),
            'too big' => UploadedFile::fake()->image('big.jpg', 600, 800)->size(10241),
        ];

        foreach ($cases as $file) {
            $this->post(route('web.outfit.photo.store'), ['photo' => $file], ['Accept' => 'application/json'])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('photo');
        }

        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_photo_can_be_deleted(): void
    {
        $this->post(route('web.outfit.photo.store'), ['photo' => UploadedFile::fake()->image('yo.jpg', 600, 800)]);
        $path = session('outfit.photo');

        $this->deleteJson(route('web.outfit.photo.destroy'))
            ->assertOk()
            ->assertJsonPath('photo_url', null);

        Storage::disk('local')->assertMissing($path);
    }

    public function test_generation_reports_unavailable_when_no_provider_is_configured(): void
    {
        $this->postJson(route('web.outfit.model.generate'), ['consent' => 1])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Primero sube o toma una foto.');

        $this->post(route('web.outfit.photo.store'), ['photo' => UploadedFile::fake()->image('yo.jpg', 600, 800)]);

        $this->postJson(route('web.outfit.model.generate'), ['consent' => 1])
            ->assertStatus(503)
            ->assertJsonPath('ai_available', false)
            ->assertJsonPath('model.status', null);
    }

    public function test_fashn_try_on_applies_top_and_bottom_and_keeps_result_private(): void
    {
        config(['services.fashn.api_key' => 'test-key']);
        Storage::fake('public');
        Storage::disk('public')->put('productos/polo.png', UploadedFile::fake()->image('polo.png', 300, 300)->getContent());
        Storage::disk('public')->put('productos/pantalon.png', UploadedFile::fake()->image('pantalon.png', 300, 300)->getContent());

        $polo = $this->variant('POLO PIQUE CLASICO', 'M', 'Negro', 49.9, 2);
        $polo->update(['imagen' => 'productos/polo.png']);
        $pantalon = $this->variant('PANTALON BRATZ', '32', 'Beige', 89.9, 2);
        $pantalon->update(['imagen' => 'productos/pantalon.png']);

        ob_start();
        imagejpeg(imagecreatetruecolor(20, 20));
        $result = base64_encode((string) ob_get_clean());

        $runs = 0;
        Http::fake([
            'api.fashn.ai/v1/run' => function () use (&$runs) {
                $runs++;

                return Http::response(['id' => "pred-{$runs}", 'error' => null]);
            },
            'api.fashn.ai/v1/status/*' => Http::response(['status' => 'completed', 'output' => ["data:image/jpeg;base64,{$result}"], 'error' => null]),
        ]);

        $this->post(route('web.outfit.photo.store'), ['photo' => UploadedFile::fake()->image('yo.jpg', 600, 800)]);
        $this->postJson(route('web.outfit.model.generate'), ['consent' => 1])->assertOk()->assertJsonPath('model.status', 'completed');

        $this->postJson(route('web.outfit.try-on'), ['variant_ids' => [$pantalon->id, $polo->id]])
            ->assertOk()
            ->assertJsonPath('model.status', 'processing');

        // Primera consulta: termina el polo y se encadena el pantalón; segunda: resultado final.
        $this->getJson(route('web.outfit.model.status'))->assertJsonPath('model.status', 'processing');
        $this->getJson(route('web.outfit.model.status'))
            ->assertJsonPath('model.status', 'completed')
            ->assertJsonPath('model.variant_ids', [$pantalon->id, $polo->id]);

        $this->assertSame(2, $runs);
        Http::assertSent(fn ($request) => $request->url() === 'https://api.fashn.ai/v1/run'
            && $request->hasHeader('Authorization', 'Bearer test-key')
            && $request['model_name'] === 'tryon-v1.6'
            && $request['inputs']['category'] === 'tops'
            && $request['inputs']['return_base64'] === true
            && str_starts_with($request['inputs']['model_image'], 'data:image/jpeg;base64,')
            && str_starts_with($request['inputs']['garment_image'], 'data:image/'));
        Http::assertSent(fn ($request) => $request->url() === 'https://api.fashn.ai/v1/run'
            && $request['inputs']['category'] === 'bottoms');

        $this->get(route('web.outfit.model.show'))->assertOk();
        $this->flushSession();
        $this->get(route('web.outfit.model.show'))->assertNotFound();
    }

    public function test_fashn_try_on_reports_garments_without_photo(): void
    {
        config(['services.fashn.api_key' => 'test-key']);
        Http::fake();
        $polo = $this->variant('POLO PIQUE CLASICO', 'M', 'Negro', 49.9, 2);

        $this->post(route('web.outfit.photo.store'), ['photo' => UploadedFile::fake()->image('yo.jpg', 600, 800)]);
        $this->postJson(route('web.outfit.model.generate'), ['consent' => 1])->assertOk();

        $this->postJson(route('web.outfit.try-on'), ['variant_ids' => [$polo->id]])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Estas prendas aún no tienen foto en el catálogo y no se pueden probar: Polo Pique Clasico.')
            ->assertJsonPath('model.status', 'completed');

        Http::assertNothingSent();
    }

    public function test_configured_provider_requires_consent_and_serves_result_to_owner_only(): void
    {
        $this->app->bind(OutfitAiGenerator::class, fn () => new class extends UnconfiguredOutfitAiGenerator
        {
            public function isAvailable(): bool
            {
                return true;
            }

            public function providerName(): ?string
            {
                return 'Proveedor de prueba';
            }

            public function generateModel(string $photoPath): OutfitAiResult
            {
                Storage::disk('local')->put('outfit/results/modelo.jpg', 'imagen');

                return OutfitAiResult::completed('outfit/results/modelo.jpg');
            }
        });

        $this->post(route('web.outfit.photo.store'), ['photo' => UploadedFile::fake()->image('yo.jpg', 600, 800)]);

        $this->postJson(route('web.outfit.model.generate'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('consent');

        $this->postJson(route('web.outfit.model.generate'), ['consent' => 1])
            ->assertOk()
            ->assertJsonPath('model.status', OutfitAiResult::COMPLETED);

        $this->get(route('web.outfit.model.show'))->assertOk();

        $this->flushSession();

        $this->get(route('web.outfit.model.show'))->assertNotFound();
    }

    public function test_finishing_outfit_revalidates_stock_and_adds_to_cart(): void
    {
        $polo = $this->variant('POLO PIQUE CLASICO', 'M', 'Negro', 49.9, 2);
        $pantalon = $this->variant('PANTALON CHINO', '32', 'Beige', 89.9, 0);

        $this->postJson(route('web.outfit.summary'), ['variant_ids' => [$polo->id, $pantalon->id]])
            ->assertOk()
            ->assertJsonPath('all_available', false)
            ->assertJsonPath('lines.1.reason', 'Sin stock en esta talla y color.')
            ->assertJsonPath('total', 49.9)
            ->assertJsonMissingPath('variants');

        $this->postJson(route('web.outfit.cart'), ['variant_ids' => [$polo->id, $pantalon->id]])
            ->assertStatus(409);
        $this->assertSame([], session('cart.items', []));

        $this->postJson(route('web.outfit.cart'), ['variant_ids' => [$polo->id]])
            ->assertOk()
            ->assertJsonPath('redirect', route('web.cart.index'));

        $this->assertSame(1, session("cart.items.{$polo->id}.qty"));
        $this->assertSame(49.9, session("cart.items.{$polo->id}.price"));
    }

    public function test_garments_without_price_cannot_be_added_to_cart(): void
    {
        $sinPrecio = $this->variant('CAMISERO PIKE', 'L', 'Azul', 0, 3);

        $this->postJson(route('web.outfit.cart'), ['variant_ids' => [$sinPrecio->id]])
            ->assertStatus(409)
            ->assertJsonPath('lines.0.reason', 'Precio por confirmar: esta prenda todavía no se puede comprar.');

        $this->assertSame([], session('cart.items', []));
    }

    public function test_outfit_allows_only_one_top_and_one_bottom(): void
    {
        $first = $this->variant('POLO PIQUE CLASICO', 'M', 'Negro', 49.9, 2);
        $second = $this->variant('CAMISA OXFORD', 'M', 'Celeste', 99.9, 2);

        $this->postJson(route('web.outfit.summary'), ['variant_ids' => [$first->id, $second->id]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('variant_ids');
    }

    public function test_purge_command_removes_expired_files(): void
    {
        $disk = Storage::disk('local');
        $disk->put('outfit/photos/vieja.jpg', 'x');
        $disk->put('outfit/photos/nueva.jpg', 'x');
        touch($disk->path('outfit/photos/vieja.jpg'), now()->subHours(25)->getTimestamp());

        $this->artisan('outfit:purge')->assertSuccessful();

        $disk->assertMissing('outfit/photos/vieja.jpg');
        $disk->assertExists('outfit/photos/nueva.jpg');
    }

    private function variant(string $name, string $size, string $color, float $price, int $stock): Producto
    {
        return Producto::query()->create([
            'name' => $name,
            'default_code' => str_replace(' ', '-', $name).'-'.$size.'-'.$color,
            'talla' => $size,
            'color' => $color,
            'price' => $price,
            'stock' => $stock,
            'zazu_company_id' => 1,
        ]);
    }
}

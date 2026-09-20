<?php

namespace Tests\Feature;

use App\Models\Admin\Combo;
use App\Models\Admin\Producto;
use App\Services\ComboCatalogImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ComboManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_combo_can_be_uploaded_and_rendered_in_public_menu(): void
    {
        $this->withoutMiddleware();
        Storage::fake('public');

        $this->post('/admin/combos', [
            'name' => 'Combo verano',
            'image' => UploadedFile::fake()->createWithContent('combo.png', $this->png()),
            'url' => '/productos',
            'status' => 1,
        ])->assertRedirect();

        $combo = Combo::query()->firstOrFail();

        Storage::disk('public')->assertExists($combo->imagen);
        $this->get('/')
            ->assertOk()
            ->assertSee('Combo verano')
            ->assertSee('/productos');
    }

    public function test_overshark_catalog_imports_prices_composition_and_product_links(): void
    {
        $product = Producto::query()->create([
            'name' => 'CLASICO',
            'stock' => 20,
            'price' => 17,
        ]);

        $result = app(ComboCatalogImportService::class)->import();

        $this->assertSame(52, $result['created']);
        $this->assertSame(52, Combo::query()->count());
        $this->assertDatabaseHas('combos', [
            'name' => 'Promo Waflera',
            'brand' => 'Overshark',
            'modality' => 'Promoción',
            'price' => 99,
        ]);

        $combo = Combo::query()->where('name', 'Combo Diva')->firstOrFail();

        $this->assertSame('choice', $combo->selection_mode);
        $this->assertSame(8, $combo->selection_limit);
        $this->assertCount(7, $combo->displayItems());
        $this->assertDatabaseHas('combo_producto', [
            'combo_id' => Combo::query()->where('name', '10x99')->firstOrFail()->id,
            'producto_id' => $product->id,
            'cantidad' => 10,
        ]);
    }

    public function test_customer_can_choose_combo_variants_and_add_the_combo_price_to_cart(): void
    {
        $small = Producto::query()->create([
            'name' => 'WAFFLE',
            'talla' => 'S',
            'color' => 'Negro',
            'stock' => 4,
            'price' => 35,
        ]);
        $medium = Producto::query()->create([
            'name' => 'WAFFLE',
            'talla' => 'M',
            'color' => 'Azul',
            'stock' => 4,
            'price' => 35,
        ]);
        $combo = Combo::query()->create([
            'name' => '5x99',
            'brand' => 'Overshark',
            'price' => 99,
            'items' => [['zazu' => 'WAFFLE', 'cantidad' => 2]],
            'selection_mode' => 'fixed',
            'status' => true,
        ]);

        $this->get(route('web.combos.show', $combo))
            ->assertOk()
            ->assertSee('Elige talla y color')
            ->assertSee('Los productos repetidos se agrupan en una sola tarjeta.')
            ->assertSee('Talla')
            ->assertSee('Color')
            ->assertSee('×2 unidades')
            ->assertSee('Unidad 1')
            ->assertSee('Unidad 2');

        $response = $this->post(route('web.combos.cart.store', $combo), [
            'selections' => [$small->id, $medium->id],
        ]);
        $response->assertRedirect(route('web.cart.index'));

        $response->assertSessionHas('cart.items', function (array $items) use ($combo, $small, $medium): bool {
            $comboItem = collect($items)->first(fn (array $item): bool => ($item['combo_id'] ?? null) === $combo->id);

            return $comboItem !== null
                && $comboItem['price'] === 99.0
                && collect($comboItem['selections'])->pluck('variant_id')->all() === [$small->id, $medium->id]
                && collect($comboItem['selections'])->pluck('color')->all() === ['Negro', 'Azul'];
        });

        $this->get(route('web.cart.index'))
            ->assertOk()
            ->assertSee('5x99')
            ->assertSee('Negro')
            ->assertSee('Azul');
    }

    private function png(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
    }
}

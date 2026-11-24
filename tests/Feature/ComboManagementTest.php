<?php

namespace Tests\Feature;

use App\Models\Admin\Combo;
use App\Models\Admin\Producto;
use App\Models\Brand;
use App\Http\Middleware\AuthenticateJwt;
use App\Services\ComboCatalogImportService;
use App\Models\User;
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
            'brand_id' => Brand::query()->where('slug', 'overshark')->firstOrFail()->id,
            'name' => 'Combo verano',
            'image' => UploadedFile::fake()->createWithContent('combo.png', $this->png()),
            'url' => '/productos',
            'status' => 1,
        ])->assertRedirect();

        $combo = Combo::query()->firstOrFail();

        Storage::disk('public')->assertExists($combo->imagen);
        $this->withSession(['storefront.line' => 'hombre'])
            ->get('/')
            ->assertOk()
            ->assertSee(route('web.combos.index'), false);
    }

    public function test_combos_page_lists_buyable_combos_once_and_names_packs_by_product(): void
    {
        Producto::query()->create(['name' => 'CLASICO', 'default_code' => 'CL-M', 'talla' => 'M', 'color' => 'NEGRO', 'stock' => 20, 'price' => 30, 'zazu_company_id' => 1]);
        $combo = fn (string $name, string $modality, array $items, float $price = 99) => Combo::query()->create([
            'name' => $name, 'brand' => 'Overshark', 'modality' => $modality, 'price' => $price,
            'imagen' => 'images/default-hero-banner.png', 'items' => $items, 'status' => true,
        ]);

        $combo('10x99', 'Live', [['zazu' => 'CLASICO', 'cantidad' => 10]]);
        $combo('10x99', 'Publicidad', [['zazu' => 'CLASICO', 'cantidad' => 10]]);
        $combo('Promo Agotada', 'Promoción', [['zazu' => 'POLO SIN STOCK', 'cantidad' => 2]]);

        $response = $this->get(route('web.combos.index'))->assertOk();

        $response->assertSee('Clasico x10')->assertSee('S/ 9.90 c/u')->assertDontSee('Promo Agotada');
        $this->assertSame(1, substr_count($response->getContent(), 'Clasico x10'));
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
            ->assertSee('Usar la misma elección en todas')
            ->assertSee('Talla')
            ->assertSee('Color')
            ->assertSee('×2 unidades')
            ->assertSee('TU COMBO')
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

    public function test_admin_can_search_combos_by_name_brand_modality_or_included_product(): void
    {
        $this->withoutMiddleware(AuthenticateJwt::class);
        $this->actingAs(User::factory()->create());

        Combo::query()->create([
            'name' => 'Combo Bravos',
            'brand' => 'BRAVOS',
            'modality' => 'Promoción',
            'items' => [['zazu' => 'POLO NERU', 'cantidad' => 2]],
            'status' => true,
        ]);
        Combo::query()->create([
            'name' => 'Combo Girls',
            'brand' => 'OVERSHARK GIRLS',
            'modality' => 'Live',
            'items' => [['zazu' => 'BABY TY', 'cantidad' => 1]],
            'status' => true,
        ]);

        $this->get(route('admin.combos.index', ['search' => 'POLO NERU']))
            ->assertOk()
            ->assertSee('Combo Bravos')
            ->assertDontSee('Combo Girls');
    }

    public function test_admin_edits_combo_with_garment_alternatives_for_the_outfit_page(): void
    {
        $this->withoutMiddleware(AuthenticateJwt::class);
        $this->actingAs(User::factory()->create());

        $make = fn (string $name, string $color, string $size) => Producto::query()->create([
            'name' => $name, 'default_code' => "{$name}-{$color}-{$size}", 'color' => $color, 'talla' => $size,
            'stock' => 5, 'price' => 49.9, 'zazu_company_id' => 1,
        ]);
        $clasico = $make('CLASICO', 'NEGRO', 'M');
        $slim = $make('SLIM FIT', 'AZUL', 'L');
        $pant = $make('PANTALON BRATZ', 'BEIGE', 'M');
        $combo = Combo::query()->create(['name' => 'Borrador', 'items' => [], 'status' => false, 'selection_mode' => 'fixed']);

        $this->get(route('admin.combos.edit', $combo))->assertOk()->assertSee('Mostrar en «Arma tu outfit»', false);

        $this->put(route('admin.combos.update', $combo), [
            'name' => 'Combo Polo', 'brand_id' => Brand::query()->where('slug', 'overshark')->firstOrFail()->id, 'price' => 99, 'notes' => 'Polo + Pantalón',
            'outfit' => 1, 'status' => 1,
            'products' => [
                ['producto_id' => $clasico->id, 'grupo' => 'Polo', 'cantidad' => 1],
                ['producto_id' => $slim->id, 'grupo' => 'Polo', 'cantidad' => 1],
                ['producto_id' => $pant->id, 'grupo' => '', 'cantidad' => 1],
            ],
        ])->assertRedirect(route('admin.outfits.index'));

        $combo->refresh();
        $this->assertSame('Outfit', $combo->modality);
        $this->assertSame('2 unidades', $combo->quantityLabel());
        $this->assertSame([
            ['zazu' => 'CLASICO', 'cantidad' => 1, 'grupo' => 'Polo'],
            ['zazu' => 'SLIM FIT', 'cantidad' => 1, 'grupo' => 'Polo'],
            ['zazu' => 'PANTALON BRATZ', 'cantidad' => 1],
        ], $combo->items);

        // La prenda "Polo" ofrece los dos productos; el pantalón queda como prenda fija.
        $slots = app(\App\Services\ComboSelectionService::class)->slots($combo);
        $this->assertSame(['Polo', 'PANTALON BRATZ'], $slots->pluck('label')->all());
        $this->assertEqualsCanonicalizing([$clasico->id, $slim->id], $slots[0]['variants']->pluck('id')->all());

        $this->get(route('web.outfit'))->assertOk()->assertSee('Combo Polo')->assertSee('Polo + Pantalón');
        $this->get(route('admin.outfits.index'))->assertOk()->assertSee('Combo Polo');
        $this->get(route('admin.combos.index'))->assertOk()->assertDontSee('Combo Polo');
    }

    public function test_a_numeric_garment_name_is_rejected_because_quantity_has_its_own_column(): void
    {
        $this->withoutMiddleware(AuthenticateJwt::class);
        $this->actingAs(User::factory()->create());

        $polo = Producto::query()->create([
            'name' => 'CLASICO', 'default_code' => 'CLASICO-NEGRO-M', 'color' => 'NEGRO', 'talla' => 'M', 'stock' => 5, 'price' => 49.9, 'zazu_company_id' => 1,
        ]);
        $combo = Combo::query()->create(['name' => 'Borrador', 'items' => [], 'status' => false, 'selection_mode' => 'fixed']);

        $this->put(route('admin.combos.update', $combo), [
            'name' => 'Combito', 'brand_id' => Brand::query()->where('slug', 'overshark')->firstOrFail()->id, 'price' => 89,
            'products' => [['producto_id' => $polo->id, 'grupo' => '2', 'cantidad' => 1]],
        ])->assertSessionHasErrors('products.0.grupo');

        // Con el nombre de la prenda y la cantidad en su columna, el cliente arma 2 unidades.
        $this->put(route('admin.combos.update', $combo), [
            'name' => 'Combito', 'brand_id' => Brand::query()->where('slug', 'overshark')->firstOrFail()->id, 'price' => 89,
            'products' => [['producto_id' => $polo->id, 'grupo' => 'Polo', 'cantidad' => 2]],
        ])->assertSessionHasNoErrors();
        $slots = app(\App\Services\ComboSelectionService::class)->slots($combo->refresh());
        $this->assertSame(2, $slots[0]['quantity']);
    }

    public function test_a_wide_flyer_spans_the_row_and_uses_the_mobile_image_on_phones(): void
    {
        $this->withoutMiddleware(AuthenticateJwt::class);
        $this->actingAs(User::factory()->create());
        Storage::fake('public');
        Producto::query()->create(['name' => 'CLASICO', 'default_code' => 'CL-M', 'talla' => 'M', 'color' => 'NEGRO', 'stock' => 20, 'price' => 30, 'zazu_company_id' => 1]);

        $this->post('/admin/combos', [
            'brand_id' => Brand::query()->where('slug', 'overshark')->firstOrFail()->id,
            'name' => 'Polos clásicos',
            'price' => 99,
            'image' => UploadedFile::fake()->image('banner.png', 1200, 400),
            'image_mobile' => UploadedFile::fake()->image('movil.png', 800, 800),
            'status' => 1,
        ])->assertSessionHasNoErrors()->assertRedirect();

        $combo = Combo::query()->firstOrFail();
        $combo->update(['items' => [['zazu' => 'CLASICO', 'cantidad' => 3]]]);
        Storage::disk('public')->assertExists($combo->imagen_movil);
        $this->assertTrue($combo->hasWideImage());

        $this->get(route('web.combos.index'))->assertOk()
            ->assertSee('<li class="col-span-full">', false)
            ->assertSee('<source media="(max-width: 767px)" srcset="'.$combo->mobileImageUrl().'">', false);

        // Quitar la imagen para celular la borra y el celular vuelve a usar la de escritorio.
        $mobile = $combo->imagen_movil;
        $this->put(route('admin.combos.update', $combo), [
            'brand_id' => $combo->brand_id, 'name' => $combo->name, 'price' => 99, 'status' => 1, 'remove_image_mobile' => 1,
            'products' => [],
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertNull($combo->fresh()->imagen_movil);
        Storage::disk('public')->assertMissing($mobile);
    }

    private function png(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
    }

    public function test_a_choice_combo_can_be_created_and_edited_from_the_panel_for_any_brand(): void
    {
        $this->withoutMiddleware(AuthenticateJwt::class)->actingAs(User::factory()->create());
        Storage::fake('public');
        $top = Producto::query()->create(['name' => 'BABY TY', 'default_code' => 'BT-S', 'talla' => 'S', 'color' => 'NEGRO', 'stock' => 20, 'price' => 17]);
        $tee = Producto::query()->create(['name' => 'TOP RIB', 'default_code' => 'TR-S', 'talla' => 'S', 'color' => 'ROSA', 'stock' => 20, 'price' => 17]);
        $girls = Brand::query()->where('slug', 'overshark-girls')->firstOrFail();

        $this->post('/admin/combos', [
            'brand_id' => $girls->id,
            'name' => 'Elige 9 Girls',
            'price' => 99,
            'selection_mode' => 'choice',
            'selection_limit' => 9,
            'products' => [['producto_id' => $top->id, 'cantidad' => 1], ['producto_id' => $tee->id, 'cantidad' => 1]],
            'status' => 1,
        ])->assertSessionHasNoErrors();

        $combo = Combo::query()->where('name', 'Elige 9 Girls')->firstOrFail();
        $this->assertSame('choice', $combo->selection_mode);
        $this->assertSame(9, $combo->selection_limit);
        $this->assertSame(9, $combo->units());

        // El formulario de edición muestra el tipo y el límite guardados.
        $this->get(route('admin.combos.edit', $combo))->assertOk()->assertSee('value="choice" selected', false)->assertSee('value="9"', false);

        // Volver a prendas fijas borra el límite de elección.
        $this->put(route('admin.combos.update', $combo), [
            'brand_id' => $girls->id, 'name' => 'Elige 9 Girls', 'price' => 99, 'selection_mode' => 'fixed', 'status' => 1,
            'products' => [['producto_id' => $top->id, 'cantidad' => 2]],
        ])->assertSessionHasNoErrors();
        $this->assertSame('fixed', $combo->fresh()->selection_mode);
        $this->assertNull($combo->fresh()->selection_limit);
    }

    public function test_a_choice_combo_needs_a_limit_and_products_to_choose_from(): void
    {
        $this->withoutMiddleware(AuthenticateJwt::class)->actingAs(User::factory()->create());
        Storage::fake('public');
        $product = Producto::query()->create(['name' => 'CLASICO', 'default_code' => 'CL-M', 'talla' => 'M', 'color' => 'NEGRO', 'stock' => 20, 'price' => 17]);
        $bravos = Brand::query()->where('slug', 'bravos')->firstOrFail();

        $this->post('/admin/combos', ['brand_id' => $bravos->id, 'name' => 'Sin limite', 'selection_mode' => 'choice', 'products' => [['producto_id' => $product->id]]])
            ->assertSessionHasErrors('selection_limit');
        $this->post('/admin/combos', ['brand_id' => $bravos->id, 'name' => 'Sin productos', 'selection_mode' => 'choice', 'selection_limit' => 3])
            ->assertSessionHasErrors('products');
        $this->assertSame(0, Combo::query()->whereIn('name', ['Sin limite', 'Sin productos'])->count());
    }

}

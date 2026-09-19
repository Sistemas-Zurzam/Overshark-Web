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

    private function png(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
    }
}

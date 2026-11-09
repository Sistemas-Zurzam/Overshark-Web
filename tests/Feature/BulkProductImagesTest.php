<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateJwt;
use App\Models\Admin\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BulkProductImagesTest extends TestCase
{
    use RefreshDatabase;

    private function variant(string $color): void
    {
        Producto::query()->create([
            'name' => 'CAMISERO PIQUE', 'default_code' => 'CP-'.$color, 'color' => $color, 'talla' => 'M', 'stock' => 5, 'price' => 80,
            'zazu_company_id' => 1, 'empresa_nombre' => 'OVERSHARK PERU S.A.C',
        ]);
    }

    public function test_files_named_by_order_and_color_use_the_product_chosen_in_the_form(): void
    {
        $this->withoutMiddleware(AuthenticateJwt::class);
        $this->actingAs(User::factory()->create());
        Storage::fake('local');
        $this->variant('PERLA');
        $this->variant('P. ROSA');
        $this->variant('MELANGE O.');
        $this->variant('NEGRO');
        $this->variant('NEGRO MATE');

        $this->post(route('admin.productos.images.preview'), [
            'product' => 'Camisero Pique',
            'images' => [
                UploadedFile::fake()->image('01-perla.png'),
                UploadedFile::fake()->image('11-palo-rosa.png'),
                UploadedFile::fake()->image('09-melange.png'),
                UploadedFile::fake()->image('12-negro.png'),
                UploadedFile::fake()->image('12-inexistente.png'),
            ],
        ])->assertRedirect(route('admin.productos.images.bulk'));

        $manifest = session('admin.bulk_product_images.v2');
        $this->assertNotNull($manifest);

        [$perla, $rosa, $melange, $negro, $missing] = $manifest['items'];
        $this->assertSame(['CAMISERO PIQUE', 'PERLA', 1, null], [$perla['product_name'], $perla['color'], $perla['order'], $perla['error']]);
        // Zazu abrevia los colores del catálogo: "palo rosa" es "P. ROSA" y "melange" es "MELANGE O.".
        $this->assertSame(['P. ROSA', 11, null], [$rosa['color'], $rosa['order'], $rosa['error']]);
        $this->assertSame(['MELANGE O.', 9, null], [$melange['color'], $melange['order'], $melange['error']]);
        // "negro" existe exacto: no se confunde con "NEGRO MATE".
        $this->assertSame(['NEGRO', null], [$negro['color'], $negro['error']]);
        $this->assertNotNull($missing['error']);
        $this->assertNotNull($perla['temp_path']);
    }
}

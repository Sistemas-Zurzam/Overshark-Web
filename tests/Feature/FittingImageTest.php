<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateJwt;
use App\Models\Admin\Producto;
use App\Models\User;
use App\Support\FittingImages;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FittingImageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->withoutMiddleware(AuthenticateJwt::class);
        $this->actingAs(User::factory()->create());
    }

    public function test_admin_uploads_transparent_garment_and_outfit_builder_uses_it(): void
    {
        $polo = $this->polo();

        $this->post(route('admin.productos.fitting-image', $polo), [
            'color' => 'Negro',
            'imagen_probador' => UploadedFile::fake()->createWithContent('polo.png', $this->png(transparent: true)),
        ])->assertSessionHasNoErrors()->assertRedirect();

        Storage::disk('public')->assertExists(FittingImages::path('POLO PIQUE CLASICO', 'Negro'));

        $this->get(route('web.outfit.show'))
            ->assertOk()
            ->assertSee('probador\/'.sha1('POLO PIQUE CLASICO'), false);

        $this->delete(route('admin.productos.fitting-image.destroy', $polo), ['color' => 'Negro'])->assertRedirect();
        Storage::disk('public')->assertMissing(FittingImages::path('POLO PIQUE CLASICO', 'Negro'));
    }

    public function test_garment_photo_with_background_is_rejected(): void
    {
        $polo = $this->polo();

        $this->post(route('admin.productos.fitting-image', $polo), [
            'color' => 'Negro',
            'imagen_probador' => UploadedFile::fake()->createWithContent('polo.png', $this->png(transparent: false)),
        ])->assertSessionHasErrors('imagen_probador');

        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    private function polo(): Producto
    {
        return Producto::query()->create([
            'name' => 'POLO PIQUE CLASICO',
            'default_code' => 'POLO-M-NEGRO',
            'talla' => 'M',
            'color' => 'Negro',
            'price' => 49.9,
            'stock' => 3,
            'zazu_company_id' => 1,
        ]);
    }

    private function png(bool $transparent): string
    {
        $image = imagecreatetruecolor(300, 300);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, $transparent ? imagecolorallocatealpha($image, 0, 0, 0, 127) : imagecolorallocate($image, 255, 255, 255));
        imagefilledrectangle($image, 80, 60, 220, 260, imagecolorallocate($image, 20, 40, 120));

        ob_start();
        imagepng($image);

        return (string) ob_get_clean();
    }
}

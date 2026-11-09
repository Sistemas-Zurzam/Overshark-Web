<?php

namespace Tests\Feature;

use App\Support\ImageOptimizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageOptimizerTest extends TestCase
{
    /** Un PNG de "foto" (ruido) grande, como los que sube el equipo desde el celular o el estudio. */
    private function bigPng(int $width, int $height): string
    {
        mt_srand(7);
        $image = imagecreatetruecolor($width, $height);
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                imagesetpixel($image, $x, $y, (min(255, intdiv($x * 255, $width) + mt_rand(0, 24)) << 16) | (min(255, intdiv($y * 255, $height) + mt_rand(0, 24)) << 8) | min(255, 120 + mt_rand(0, 24)));
            }
        }
        ob_start();
        imagepng($image, null, 6);
        imagedestroy($image);

        return (string) ob_get_clean();
    }

    public function test_large_png_is_resized_and_stored_as_a_much_lighter_webp(): void
    {
        Storage::fake('public');
        $png = $this->bigPng(1000, 1250);

        $path = ImageOptimizer::storeContents($png, 'productos', 'png', 800);

        $this->assertStringEndsWith('.webp', $path);
        Storage::disk('public')->assertExists($path);
        $stored = Storage::disk('public')->get($path);
        $this->assertLessThan(strlen($png) / 2, strlen($stored));
        [$width, $height] = getimagesizefromstring($stored);
        $this->assertSame(800, max($width, $height));
        $this->assertSame(640, $width); // se conserva la proporción 4:5
    }

    public function test_banners_keep_a_wider_limit_than_products(): void
    {
        $this->assertGreaterThan(ImageOptimizer::PRODUCT_MAX_SIDE, ImageOptimizer::BANNER_MAX_SIDE);
        Storage::fake('public');

        $path = ImageOptimizer::storeContents($this->bigPng(1500, 500), 'banners', 'png', 1200);

        [$width] = getimagesizefromstring(Storage::disk('public')->get($path));
        $this->assertSame(1200, $width);
    }

    public function test_uploaded_file_is_optimized_and_a_file_that_is_not_an_image_is_stored_as_it_came(): void
    {
        Storage::fake('public');

        $path = ImageOptimizer::storeUploaded(UploadedFile::fake()->createWithContent('foto.png', $this->bigPng(800, 800)), 'productos');
        $this->assertStringEndsWith('.webp', $path);

        $raw = ImageOptimizer::storeContents('no es una imagen', 'productos', 'txt');
        $this->assertStringEndsWith('.txt', $raw);
        $this->assertSame('no es una imagen', Storage::disk('public')->get($raw));
    }
}

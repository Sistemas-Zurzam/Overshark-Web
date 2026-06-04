<?php

namespace Tests\Feature;

use App\Models\Admin\BannerPortada;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BannerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_banner_image_can_be_uploaded_and_shown_on_storefront(): void
    {
        $this->withoutMiddleware();
        Storage::fake('public');

        $this->post('/admin/banners', [
            'name' => 'Banner de prueba',
            'image' => UploadedFile::fake()->createWithContent('banner.png', $this->png()),
            'time' => 8,
            'modo' => 'cover',
            'status' => 1,
        ])->assertRedirect();

        $banner = BannerPortada::query()->firstOrFail();

        Storage::disk('public')->assertExists($banner->image_path);
        $this->get('/')->assertOk()->assertSee($banner->imageUrl());
    }

    private function png(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
    }
}

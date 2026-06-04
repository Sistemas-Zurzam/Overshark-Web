<?php

namespace Tests\Feature;

use App\Models\BrandSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BrandSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_logo_and_icon(): void
    {
        $this->withoutMiddleware();
        Storage::fake('public');

        $this->put('/admin/identidad-visual', [
            'logo' => UploadedFile::fake()->createWithContent('logo.png', $this->png()),
            'icon' => UploadedFile::fake()->createWithContent('icon.png', $this->png()),
        ])->assertRedirect();

        $brand = BrandSetting::current();

        Storage::disk('public')->assertExists($brand->logo_path);
        Storage::disk('public')->assertExists($brand->icon_path);
    }

    private function png(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
    }
}

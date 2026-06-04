<?php

namespace Tests\Feature;

use App\Models\Admin\Combo;
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

    private function png(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
    }
}

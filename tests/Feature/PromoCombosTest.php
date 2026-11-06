<?php

namespace Tests\Feature;

use App\Models\Admin\Combo;
use App\Models\Admin\Producto;
use App\Support\PromoCombos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Combos con flyer de "Elige tu combo" (App\Support\PromoCombos): se crean como combos del panel. */
class PromoCombosTest extends TestCase
{
    use RefreshDatabase;

    private function product(string $name, string $company = 'OVERSHARK PERU S.A.C'): void
    {
        Producto::query()->create(['name' => $name, 'default_code' => 'SKU-'.$name, 'color' => 'NEGRO', 'talla' => 'M', 'stock' => 50, 'price' => 17, 'empresa_nombre' => $company]);
    }

    public function test_creates_the_17_combos_once_with_their_flyer_price_and_garments(): void
    {
        $this->product('CLASICO');

        $first = PromoCombos::create();
        $second = PromoCombos::create();

        $this->assertCount(17, $first['created']);
        $this->assertSame(17, count($second['existing']));
        $this->assertSame([], $second['created']);
        $this->assertSame(17, Combo::query()->where('source_key', 'like', 'promo-web:%')->count());
        $this->assertContains('JEANS', $first['missing']); // lo que no está en el catálogo se avisa

        $combo = Combo::query()->where('source_key', 'promo-web:clasicos-jersey')->firstOrFail();
        $this->assertSame(['Clásicos jersey', 'Overshark', '99.00', 'images/combos/promo-clasicos-jersey.webp'], [$combo->name, $combo->brand, (string) $combo->price, $combo->imagen]);
        $this->assertSame([['zazu' => 'CLASICO', 'cantidad' => 9]], $combo->items);
        $this->assertSame(['Bravos', 'Girls'], [
            Combo::query()->where('source_key', 'promo-web:bravos-poleras-neru')->value('brand'),
            Combo::query()->where('source_key', 'promo-web:girls-9-prendas')->value('brand'),
        ]);
        $girls = Combo::query()->where('source_key', 'promo-web:girls-9-prendas')->firstOrFail();
        $this->assertSame(['choice', 9], [$girls->selection_mode, $girls->selection_limit]);

        foreach (array_keys(PromoCombos::definitions()) as $key) {
            $this->assertFileExists(public_path("images/combos/promo-{$key}.webp"));
        }
    }

    public function test_what_the_admin_edits_is_kept_and_the_store_shows_the_flyer_card_over_older_duplicates(): void
    {
        $this->product('CLASICO');
        // El mismo combo que ya traía el catálogo importado (sin flyer), creado después a propósito.
        Combo::query()->create(['name' => '9x99', 'brand' => 'Overshark', 'modality' => 'Publicidad', 'price' => 99, 'imagen' => 'images/default-hero-banner.png',
            'items' => [['zazu' => 'CLASICO', 'cantidad' => 9]], 'selection_mode' => 'fixed', 'status' => true]);
        PromoCombos::create();

        $this->get(route('web.combos.index', ['marca' => 'Overshark']))
            ->assertOk()
            ->assertSee('Clásicos jersey')
            ->assertSee('9 × S/ 99')
            ->assertSee('/images/combos/promo-clasicos-jersey.webp', false)
            ->assertSee('ELEGIR ESTE COMBO')
            ->assertDontSee('Clasico x9'); // el duplicado sin flyer no aparece

        // El equipo cambia el precio y apaga otro combo en el panel; volver a correr el comando no lo pisa.
        Combo::query()->where('source_key', 'promo-web:clasicos-jersey')->firstOrFail()->update(['price' => 89.9]);
        PromoCombos::create();
        $this->assertSame('89.90', (string) Combo::query()->where('source_key', 'promo-web:clasicos-jersey')->value('price'));

        $this->get(route('web.combos.index', ['marca' => 'Overshark']))->assertSee('9 × S/ 89.9');
    }

    public function test_a_promo_without_available_garments_is_not_listed_and_each_brand_shows_its_own(): void
    {
        $this->product('POLERA NERU', 'BRAVOS');
        PromoCombos::create();

        $this->get(route('web.combos.index', ['marca' => 'Bravos']))->assertOk()->assertSee('Poleras neru')->assertSee('3 × S/ 99')->assertDontSee('Comfort pants');
        $this->get(route('web.combos.index', ['marca' => 'Overshark']))->assertOk()->assertDontSee('Poleras neru')->assertDontSee('Mom jeans');
    }
}

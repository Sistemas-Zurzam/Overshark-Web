<?php

namespace Tests\Feature;

use App\Models\Admin\Producto;
use App\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\GivesProductsColorPhotos;
use Tests\TestCase;

/** Cada línea tiene su tienda: la de Hombre (Overshark) no mezcla Bravos ni Girls. */
class LineSeparationTest extends TestCase
{
    use GivesProductsColorPhotos;
    use RefreshDatabase;

    private function product(string $name, ?string $marca, ?string $empresa, array $extra = []): Producto
    {
        return Producto::query()->create($extra + [
            'name' => $name, 'default_code' => $name, 'color' => 'NEGRO', 'talla' => 'M', 'stock' => 5, 'price' => 35,
            'zazu_company_id' => 1, 'empresa_nombre' => $empresa, 'marca' => $marca, 'zazu_source_key' => 'variante:'.$name,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->product('POLO CLASICO', 'OVERSHARK', 'OVERSHARK PERU S.A.C');
        $this->product('POLERA BOXYFIT', 'BRAVOS', 'BRAVOS');                  // Bravos por marca asignada
        $this->product('PANTALON BRATZ', null, 'BRAVOS');                     // Bravos solo por su empresa en Zazu
        $this->product('CAMISA ANTIGUA', null, null);                         // dato antiguo sin marca ni empresa: sigue en Hombre
        $this->product('BABY TY ROSA', 'OVERSHARK GIRLS', 'OVERSHARK PERU S.A.C');
    }

    public function test_the_men_catalog_has_no_bravos_and_no_girls_products(): void
    {
        $this->withSession(['storefront.line' => 'hombre'])->get(route('web.products.search'))
            ->assertOk()
            ->assertSee('Polo Clasico')->assertSee('Camisa Antigua')
            ->assertDontSee('Polera Boxyfit')->assertDontSee('Pantalon Bratz')->assertDontSee('Baby Ty');
    }

    public function test_the_men_home_best_sellers_have_no_bravos_products(): void
    {
        $this->withSession(['storefront.line' => 'hombre'])->get('/')
            ->assertOk()->assertSee('Polo Clasico')->assertDontSee('Polera Boxyfit')->assertDontSee('Pantalon Bratz');
    }

    public function test_bravos_products_still_show_in_bravos_and_a_concrete_search_still_finds_them(): void
    {
        $bravos = Brand::query()->where('slug', 'bravos')->firstOrFail();

        $this->get(route('web.products.search', ['marca' => $bravos->name]))
            ->assertOk()->assertSee('Polera Boxyfit')->assertSee('Pantalon Bratz')->assertDontSee('Polo Clasico');

        // Buscar algo concreto desde la tienda de Hombre sí lo encuentra (igual que con los productos de Girls).
        $this->withSession(['storefront.line' => 'hombre'])->get(route('web.products.search', ['q' => 'boxyfit']))
            ->assertOk()->assertSee('Polera Boxyfit');
    }

    public function test_the_women_line_is_not_affected(): void
    {
        $this->withSession(['storefront.line' => 'mujer'])->get(route('web.products.search'))
            ->assertOk()->assertSee('Baby Tee Rosa')->assertDontSee('Polera Boxyfit')->assertDontSee('Polo Clasico');
    }

    public function test_except_brand_keeps_products_without_brand_or_company(): void
    {
        $names = Producto::query()->exceptBrand('BRAVOS')->pluck('name')->all();

        $this->assertContains('POLO CLASICO', $names);
        $this->assertContains('CAMISA ANTIGUA', $names, 'marca y empresa nulas: no es de Bravos');
        $this->assertNotContains('POLERA BOXYFIT', $names);
        $this->assertNotContains('PANTALON BRATZ', $names);
        $this->assertSame(Producto::query()->count() - 2, count($names));
    }
}

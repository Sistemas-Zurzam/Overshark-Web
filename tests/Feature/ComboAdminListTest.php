<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateJwt;
use App\Models\Admin\Combo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Listado de Combos del panel: orden, paginación, precio por prenda y avisos de lo que falta. */
class ComboAdminListTest extends TestCase
{
    use RefreshDatabase;

    private function combo(string $name, ?float $price, array $extra = []): Combo
    {
        return Combo::query()->create($extra + [
            'name' => $name, 'brand' => 'Overshark', 'price' => $price, 'status' => true, 'is_draft' => false, 'imagen' => 'combos/x.webp',
            'items' => [['zazu' => 'CLASICO', 'cantidad' => 3]],
        ]);
    }

    private function list(array $query = [])
    {
        return $this->withoutMiddleware(AuthenticateJwt::class)->actingAs(User::factory()->create())->get(route('admin.combos.index', $query));
    }

    public function test_units_and_the_label_match_the_composition_and_the_choice_mode(): void
    {
        $this->assertSame(3, $this->combo('Tres', 50)->units());
        $this->assertSame('3 unidades', $this->combo('Tres b', 50)->quantityLabel());

        $choice = $this->combo('Elige', 99, ['items' => [], 'selection_mode' => 'choice', 'selection_limit' => 9]);
        $this->assertSame(9, $choice->units());
        $this->assertSame('Elige 9 unidades', $choice->quantityLabel());

        $alternatives = $this->combo('Alternativas', 99, ['items' => [['zazu' => 'A', 'cantidad' => 2, 'grupo' => 'X'], ['zazu' => 'B', 'cantidad' => 2, 'grupo' => 'X']]]);
        $this->assertSame(2, $alternatives->units(), 'las alternativas de un grupo cuentan una sola vez');
    }

    public function test_each_card_shows_the_price_per_garment_and_the_discount(): void
    {
        $this->combo('Tres polos', 60, ['compare_at_price' => 90]);

        $this->list()->assertOk()->assertSee('Tres polos')->assertSee('S/ 20.00 c/u')->assertSee('-33%')->assertSee('3 prendas');
    }

    public function test_it_warns_about_a_combo_without_own_image_price_or_composition(): void
    {
        $this->combo('Incompleto', null, ['imagen' => null, 'items' => []]);

        $this->list()->assertSee('Sin imagen propia')->assertSee('Sin precio')->assertSee('Composición pendiente');
    }

    public function test_the_list_can_be_sorted_by_price_and_name(): void
    {
        $this->combo('Zeta', 30);
        $this->combo('Alfa', 90);
        $this->combo('Medio', 60);

        $this->list(['orden' => 'precio-asc'])->assertSeeInOrder(['Zeta', 'Medio', 'Alfa']);
        $this->list(['orden' => 'precio-desc'])->assertSeeInOrder(['Alfa', 'Medio', 'Zeta']);
        $this->list(['orden' => 'nombre'])->assertSeeInOrder(['Alfa', 'Medio', 'Zeta']);
    }

    public function test_it_paginates_by_24_and_keeps_the_filters_in_the_links(): void
    {
        foreach (range(1, 30) as $i) {
            $this->combo(sprintf('Combo %02d', $i), $i);
        }

        $first = $this->list(['orden' => 'nombre']);
        $first->assertSee('Combo 24')->assertDontSee('Combo 25')->assertSee('Mostrando 1–24 de 30 combos')->assertSee('orden=nombre', false);

        $this->list(['orden' => 'nombre', 'page' => 2])->assertSee('Combo 25')->assertSee('Combo 30')->assertDontSee('Combo 24')->assertSee('Mostrando 25–30 de 30 combos');
    }
}

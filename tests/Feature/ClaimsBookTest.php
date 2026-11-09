<?php

namespace Tests\Feature;

use App\Models\LibroReclamacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class ClaimsBookTest extends TestCase
{
    use RefreshDatabase;

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'consumer_name' => 'Cliente de ejemplo',
            'document_type' => 'DNI',
            'document_number' => '75351525',
            'address' => 'Av. Arequipa 1234, Lima',
            'email' => 'cliente@example.com',
            'claim_type' => 'reclamo',
            'order_product' => 'Baby Tee Negra',
            'claim_description' => 'La prenda no corresponde a la talla elegida.',
            'expected_solution' => 'Cambio por la talla del pedido.',
        ], $overrides);
    }

    public function test_form_shows_the_single_sheet_with_provider_block_and_no_wizard(): void
    {
        $this->get(route('web.claims.create'))
            ->assertOk()
            ->assertSee('1. Datos del consumidor')
            ->assertSee('2. Detalle de la solicitud')
            ->assertSee('Proveedor responsable')
            ->assertSee('Revisar solicitud')
            ->assertDontSee('data-claim-wizard', false);
    }

    public function test_missing_required_fields_show_inline_errors_and_keep_what_was_typed(): void
    {
        $this->from(route('web.claims.create'))
            ->post(route('web.claims.submit'), ['consumer_name' => 'Ana'])
            ->assertRedirect(route('web.claims.create'))
            ->assertSessionHasErrors(['document_number', 'address', 'email', 'order_product', 'claim_description', 'expected_solution']);

        $this->followingRedirects()->from(route('web.claims.create'))
            ->post(route('web.claims.submit'), ['consumer_name' => 'Ana'])
            ->assertSee('Completa este campo para continuar.')
            ->assertSee('value="Ana"', false);

        $this->assertDatabaseCount('libro_reclamaciones', 0);
    }

    public function test_phone_order_and_guardian_are_optional_but_a_guardian_needs_document_and_contact(): void
    {
        $this->post(route('web.claims.submit'), $this->validData())->assertRedirect(route('web.claims.review'));

        $this->post(route('web.claims.submit'), $this->validData(['guardian_name' => 'Rosa Pérez']))
            ->assertSessionHasErrors(['guardian_document_number', 'guardian_contact']);
    }

    public function test_review_page_needs_a_draft_and_nothing_is_saved_until_confirming(): void
    {
        $this->get(route('web.claims.review'))->assertRedirect(route('web.claims.create'));

        $this->post(route('web.claims.submit'), $this->validData());

        $this->get(route('web.claims.review'))
            ->assertOk()
            ->assertSee('Revisa tu solicitud')
            ->assertSee('Cliente de ejemplo')
            ->assertSee('Confirmar envío')
            ->assertSee('← Editar mi solicitud');

        $this->assertDatabaseCount('libro_reclamaciones', 0);
    }

    public function test_editing_returns_to_the_form_with_the_data(): void
    {
        $this->post(route('web.claims.submit'), $this->validData(['phone' => '920332344']));

        $this->followingRedirects()->get(route('web.claims.edit'))
            ->assertSee('value="Cliente de ejemplo"', false)
            ->assertSee('value="920332344"', false);
    }

    public function test_confirming_registers_the_sheet_with_its_number_and_shows_it(): void
    {
        $this->post(route('web.claims.submit'), $this->validData(['claimed_amount' => '99.90']));

        $this->post(route('web.claims.store'))->assertRedirect(route('web.claims.registered'));

        $claim = LibroReclamacion::firstOrFail();
        $this->assertSame(sprintf('LR-%s-%06d', now()->format('Y'), $claim->id), $claim->sheet_number);
        $this->assertSame('Overshark Hombre', $claim->brand);
        $this->assertSame('Baby Tee Negra', $claim->claim_product);
        $this->assertFalse($claim->is_minor);
        $this->assertSame(LibroReclamacion::STATUS_PENDING, $claim->status);

        $this->get(route('web.claims.registered'))
            ->assertOk()
            ->assertSee('✓ Recibimos tu solicitud')
            ->assertSee('HOJA N.º '.$claim->sheet_number)
            ->assertSee('Ver constancia')
            ->assertSee('Volver a la tienda');

        $this->get(route('web.claims.receipt'))
            ->assertOk()
            ->assertSee('Constancia de reclamación')
            ->assertSee('HOJA N.º '.$claim->sheet_number)
            ->assertSee('S/ 99.90');

        // El borrador se consumió: volver a confirmar no duplica la hoja.
        $this->post(route('web.claims.store'))->assertRedirect(route('web.claims.create'));
        $this->assertDatabaseCount('libro_reclamaciones', 1);
    }

    public function test_minor_with_guardian_is_flagged(): void
    {
        $this->post(route('web.claims.submit'), $this->validData([
            'guardian_name' => 'Rosa Pérez',
            'guardian_document_type' => 'DNI',
            'guardian_document_number' => '12345678',
            'guardian_contact' => 'rosa@example.com',
        ]));
        $this->post(route('web.claims.store'));

        $claim = LibroReclamacion::firstOrFail();
        $this->assertTrue($claim->is_minor);
        $this->assertSame('rosa@example.com', $claim->guardian_contact);
    }

    public function test_supporting_document_is_stored_privately_under_the_sheet_number(): void
    {
        Storage::fake('local');

        $this->post(route('web.claims.submit'), $this->validData([
            'supporting_document' => UploadedFile::fake()->create('boleta.pdf', 120, 'application/pdf'),
        ]))->assertRedirect(route('web.claims.review'));
        $this->post(route('web.claims.store'));

        $claim = LibroReclamacion::firstOrFail();
        $this->assertStringStartsWith('reclamos/'.$claim->sheet_number.'/', $claim->supporting_document);
        Storage::disk('local')->assertExists($claim->supporting_document);
    }

    public function test_unsupported_attachment_is_rejected(): void
    {
        Storage::fake('local');

        $this->post(route('web.claims.submit'), $this->validData([
            'supporting_document' => UploadedFile::fake()->create('virus.exe', 10),
        ]))->assertSessionHasErrors('supporting_document');
    }

    public function test_a_failure_while_saving_shows_the_error_screen_without_a_sheet_number_and_keeps_the_draft(): void
    {
        $this->post(route('web.claims.submit'), $this->validData());

        LibroReclamacion::creating(fn () => throw new RuntimeException('db caída'));
        $this->post(route('web.claims.store'))->assertRedirect(route('web.claims.error'));

        $this->get(route('web.claims.error'))
            ->assertOk()
            ->assertSee('No se pudo enviar')
            ->assertSee('Tu solicitud aún no fue registrada.')
            ->assertSee('No se ha generado un número de hoja.')
            ->assertSee('Volver a intentar');

        $this->assertDatabaseCount('libro_reclamaciones', 0);

        // Reintentar con la base de nuevo disponible registra la hoja con el mismo borrador.
        LibroReclamacion::flushEventListeners();
        $this->post(route('web.claims.store'))->assertRedirect(route('web.claims.registered'));
        $this->assertDatabaseCount('libro_reclamaciones', 1);
    }

    public function test_result_screens_are_private_to_the_visitor_who_filed_the_claim(): void
    {
        $this->get(route('web.claims.registered'))->assertRedirect(route('web.claims.create'));
        $this->get(route('web.claims.receipt'))->assertRedirect(route('web.claims.create'));
        $this->get(route('web.claims.error'))->assertRedirect(route('web.claims.create'));
    }
}

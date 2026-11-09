<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TermsPageTest extends TestCase
{
    use RefreshDatabase;

    /** El HTML de la página sin los <dialog> (los modales de los otros documentos van ocultos en todas las páginas). */
    private function pageWithoutModals(string $route): string
    {
        $html = $this->get(route($route))->assertOk()->getContent();

        return preg_replace('#<dialog\b.*?</dialog>#s', '', $html);
    }

    public function test_terms_page_shows_its_eight_sections_and_links_to_the_other_legal_pages(): void
    {
        $page = $this->pageWithoutModals('web.terms');

        foreach (['Términos y condiciones', 'Proveedor responsable', '1. Identificación del proveedor', '8. Vigencia de las condiciones'] as $text) {
            $this->assertStringContainsString($text, $page);
        }
        $this->assertStringNotContainsString('1. Responsable del tratamiento', $page);
        $this->assertStringContainsString(route('web.privacy'), $page);
        $this->assertStringContainsString(route('web.claims.create'), $page);
        $this->assertSame(1, substr_count($page, '<h1'));
    }

    public function test_privacy_page_shows_its_eight_sections(): void
    {
        $page = $this->pageWithoutModals('web.privacy');

        foreach (['Política de privacidad', 'Proveedor responsable', '1. Responsable del tratamiento', '7. Tus derechos', '8. Cookies y actualizaciones'] as $text) {
            $this->assertStringContainsString($text, $page);
        }
        $this->assertStringNotContainsString('1. Identificación del proveedor', $page);
        $this->assertStringContainsString(route('web.terms'), $page);
        $this->assertSame(1, substr_count($page, '<h1'));
    }

    public function test_the_rest_of_the_store_opens_each_document_in_a_modal_but_a_document_page_has_no_duplicate(): void
    {
        $this->get(route('web.contact'))
            ->assertSee('data-legal-open="terms"', false)
            ->assertSee('data-legal-open="privacy"', false)
            ->assertSee('data-legal-modal="terms"', false)
            ->assertSee('data-legal-modal="privacy"', false)
            ->assertSee('8. Vigencia de las condiciones')
            ->assertSee('8. Cookies y actualizaciones');

        $terms = $this->get(route('web.terms'))->getContent();
        $this->assertStringNotContainsString('data-legal-modal="terms"', $terms);
        $this->assertStringContainsString('data-legal-modal="privacy"', $terms);
    }
}

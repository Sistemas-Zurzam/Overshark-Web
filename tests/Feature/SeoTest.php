<?php

namespace Tests\Feature;

use App\Models\Admin\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    private function product(string $name, array $overrides = []): Producto
    {
        return Producto::query()->create(array_merge([
            'name' => $name, 'default_code' => strtoupper(substr(md5($name.json_encode($overrides)), 0, 8)), 'talla' => 'M',
            'stock' => 3, 'price' => 40, 'zazu_company_id' => 1,
        ], $overrides));
    }

    public function test_old_shop_links_redirect_permanently_to_the_product_or_the_search(): void
    {
        $jersey = $this->product('CAMISERO JERSEY');
        $this->product('CAMISERO JERSEY', ['talla' => 'L']);
        $this->product('POLO BASICO');

        // Mismo nombre que el producto en venta: va a su página.
        $this->get('/shop/camisero-jersey-melon')->assertStatus(301)->assertRedirect(route('web.products.show', $jersey->id));
        $this->get('/shop/camisero-jersey/')->assertStatus(301)->assertRedirect(route('web.products.show', $jersey->id));

        // Sin producto con ese nombre: a la búsqueda con las palabras que encuentran algo.
        $this->get('/shop/polo-oversize-azul')->assertStatus(301)->assertRedirect(route('web.products.search', ['q' => 'polo']));
        $this->get('/shop/zapatillas-runner')->assertStatus(301)->assertRedirect(route('web.products.search'));
        $this->get('/shop')->assertStatus(301)->assertRedirect(route('web.products.search'));
    }

    public function test_a_sold_out_product_is_not_a_redirect_target(): void
    {
        $this->product('CAMISERO JERSEY', ['stock' => 0]);

        $this->get('/shop/camisero-jersey')->assertRedirect(route('web.products.search'));
    }

    public function test_the_sitemap_lists_the_store_pages_and_one_url_per_product_for_sale(): void
    {
        $jersey = $this->product('CAMISERO JERSEY');
        $other = $this->product('CAMISERO JERSEY', ['talla' => 'L']);
        $soldOut = $this->product('POLO AGOTADO', ['stock' => 0]);

        $response = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $xml = $response->getContent();

        $this->assertStringContainsString('<loc>'.url('/').'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.route('web.products.search').'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.route('web.products.show', $jersey->id).'</loc>', $xml);
        $this->assertStringNotContainsString('<loc>'.route('web.products.show', $other->id).'</loc>', $xml);
        $this->assertStringNotContainsString('<loc>'.route('web.products.show', $soldOut->id).'</loc>', $xml);
        $this->assertNotFalse(simplexml_load_string($xml));
    }

    public function test_www_redirects_to_the_main_address_but_payment_webhooks_are_still_accepted(): void
    {
        config(['app.url' => 'https://overshark.com.pe']);

        $this->get('http://www.overshark.com.pe/nosotros?linea=mujer')
            ->assertStatus(301)
            ->assertRedirect('https://overshark.com.pe/nosotros?linea=mujer');
        $this->get('http://overshark.com.pe/nosotros')->assertOk();

        // Un aviso por POST a www no se redirige (Mercado Pago no repite el POST tras una redirección).
        $this->postJson('http://www.overshark.com.pe/webhooks/mercadopago?type=test', ['type' => 'test'])->assertOk();
    }
}

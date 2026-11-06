<?php

namespace Tests\Concerns;

use App\Models\Admin\Departamento;
use App\Models\Admin\Distrito;
use App\Models\Admin\Order;
use App\Models\Admin\Producto;
use App\Models\Admin\Provincia;
use App\Support\Checkout;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

/** Catálogo mínimo y compra de punta a punta (simulada) para las pruebas de cuentas de clientes. */
trait PlacesWebOrders
{
    protected Producto $polo;

    protected Distrito $miraflores;

    protected function setUpShop(): void
    {
        $this->polo = Producto::query()->create([
            'name' => 'CLASICO', 'default_code' => 'CL-N-M', 'color' => 'NEGRO', 'talla' => 'M', 'stock' => 20, 'price' => 40, 'zazu_company_id' => 1,
        ]);
        $lima = Provincia::query()->create(['departamento_id' => Departamento::query()->create(['name' => 'Lima'])->id, 'name' => 'Lima']);
        $this->miraflores = Distrito::query()->create(['provincia_id' => $lima->id, 'name' => 'Miraflores']);
        config(['services.payments.simulate' => true]);
        Http::fake();
        Mail::fake();
    }

    protected function addToCart(int $qty = 1): void
    {
        $this->post(route('web.cart.store'), ['producto_id' => $this->polo->id, 'color' => 'NEGRO', 'talla' => 'M', 'qty' => $qty]);
    }

    /** Datos del paso 2 del checkout (Miraflores, dentro de la cobertura de delivery). */
    protected function customerData(array $override = []): array
    {
        return array_merge([
            'email' => 'ana@example.com', 'nombres' => 'Ana', 'apellidos' => 'Ríos', 'phone' => '987654321',
            'doc_type' => 'DNI', 'doc_number' => '12345678', 'distrito_id' => $this->miraflores->id,
            'address' => 'Av. Larco 123', 'delivery' => 'domicilio', 'delivery_lat' => -12.1211, 'delivery_lng' => -77.0297, 'invoice' => 'boleta',
        ], $override);
    }

    /** Agrega al carrito, llena los datos y paga con tarjeta simulada. Devuelve el pedido creado. */
    protected function buy(array $customer = [], array $place = []): Order
    {
        $this->addToCart();
        $this->post(route('web.checkout.personal.store'), $this->customerData($customer))->assertSessionHasNoErrors();
        $total = $this->get(route('web.checkout.payment'))->viewData('total');
        $this->post(route('web.checkout.place'), array_merge(
            ['method' => Checkout::CARD, 'culqi_token' => 'sim_tkn_ok', 'token' => session('checkout.token'), 'terms' => 1, 'expected_total' => number_format($total, 2, '.', '')],
            $place,
        ))->assertSessionHasNoErrors();

        return Order::query()->latest('id')->first();
    }
}

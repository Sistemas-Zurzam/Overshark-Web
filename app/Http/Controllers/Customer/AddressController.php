<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Admin\Departamento;
use App\Models\CustomerAddress;
use App\Support\DeliveryCoverage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Libreta de direcciones de la cuenta. Cada consulta pasa por $account->addresses(), así que solo se ven y se
 * editan direcciones propias. Los pedidos guardan su propia copia de la dirección: borrar una no cambia el historial.
 */
class AddressController extends Controller
{
    public const ALIASES = ['Casa', 'Trabajo', 'Otro'];

    public function index(Request $request): View
    {
        return view('web.account.addresses', [
            'addresses' => $request->user('customer')->addresses()->with('distrito.provincia.departamento')->get(),
            'address' => null,
            'ubigeo' => $this->ubigeo(),
        ]);
    }

    public function edit(Request $request, int $address): View
    {
        $account = $request->user('customer');

        return view('web.account.addresses', [
            'addresses' => $account->addresses()->with('distrito.provincia.departamento')->get(),
            'address' => $account->addresses()->findOrFail($address),
            'ubigeo' => $this->ubigeo(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $account = $request->user('customer');
        $data = $this->validated($request);

        DB::transaction(function () use ($account, $data, $request): void {
            $default = ! $account->addresses()->exists() || $request->boolean('is_default');

            if ($default) {
                CustomerAddress::query()->where('customer_account_id', $account->id)->update(['is_default' => false]);
            }

            $account->addresses()->create($data + ['is_default' => $default]);
        });

        return redirect()->route('customer.addresses')->with('status', 'Guardamos tu dirección.');
    }

    public function update(Request $request, int $address): RedirectResponse
    {
        $account = $request->user('customer');
        $model = $account->addresses()->findOrFail($address);
        $data = $this->validated($request);

        DB::transaction(function () use ($account, $model, $data, $request): void {
            if ($request->boolean('is_default') && ! $model->is_default) {
                CustomerAddress::query()->where('customer_account_id', $account->id)->update(['is_default' => false]);
                $data['is_default'] = true;
            }

            $model->update($data);
        });

        return redirect()->route('customer.addresses')->with('status', 'Actualizamos tu dirección.');
    }

    public function makeDefault(Request $request, int $address): RedirectResponse
    {
        $account = $request->user('customer');
        $model = $account->addresses()->findOrFail($address);

        DB::transaction(function () use ($account, $model): void {
            CustomerAddress::query()->where('customer_account_id', $account->id)->update(['is_default' => false]);
            $model->update(['is_default' => true]);
        });

        return back()->with('status', 'Esa será tu dirección principal.');
    }

    public function destroy(Request $request, int $address): RedirectResponse
    {
        $account = $request->user('customer');
        $model = $account->addresses()->findOrFail($address);

        DB::transaction(function () use ($account, $model): void {
            $wasDefault = $model->is_default;
            $model->delete();

            if ($wasDefault) {
                $account->addresses()->first()?->update(['is_default' => true]);
            }
        });

        return redirect()->route('customer.addresses')->with('status', 'Eliminamos la dirección.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'alias' => ['required', 'in:'.implode(',', self::ALIASES)],
            'recipient' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'regex:/^9\d{8}$/'],
            'distrito_id' => ['required', 'integer', 'exists:distritos,id'],
            'address' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
        ], [
            'alias.required' => 'Elige un nombre para la dirección.',
            'alias.in' => 'Elige Casa, Trabajo u Otro.',
            'recipient.required' => 'Ingresa quién recibe el pedido.',
            'phone.required' => 'Ingresa un celular de contacto.',
            'phone.regex' => 'Ingresa un celular de 9 dígitos que empiece con 9.',
            'distrito_id.required' => 'Elige tu distrito.',
            'address.required' => 'Ingresa la dirección.',
        ]);

        // Con un punto en el mapa (guardado desde el checkout) debe estar dentro de la cobertura de delivery.
        if (filled($data['lat'] ?? null) && filled($data['lng'] ?? null) && ! DeliveryCoverage::contains((float) $data['lat'], (float) $data['lng'])) {
            throw ValidationException::withMessages(['address' => 'Esa ubicación está fuera de la cobertura de delivery.']);
        }

        return collect($data)->only(['alias', 'recipient', 'phone', 'distrito_id', 'address', 'reference', 'lat', 'lng'])->all();
    }

    private function ubigeo()
    {
        return Departamento::query()->with('provincias.distritos:id,provincia_id,name')->orderBy('name')->get(['id', 'name'])
            ->map(fn ($departamento) => [
                'id' => $departamento->id,
                'name' => $departamento->name,
                'provincias' => $departamento->provincias->sortBy('name')->values()->map(fn ($provincia) => [
                    'id' => $provincia->id,
                    'name' => $provincia->name,
                    'distritos' => $provincia->distritos->sortBy('name')->values()->map(fn ($distrito) => ['id' => $distrito->id, 'name' => $distrito->name]),
                ]),
            ]);
    }
}

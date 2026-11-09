<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Cliente;
use App\Models\CustomerAccount;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin → Clientes: quienes compraron en la tienda (tabla clientes, que llena el checkout) y las cuentas que se
 * registraron en la web (customer_accounts, con o sin compras).
 */
class ClienteController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $tab = $request->query('tab') === 'cuentas' ? 'cuentas' : 'compradores';
        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $search).'%';

        $buyers = Cliente::query()
            ->withCount('orders')
            ->withSum('orders', 'precio_total')
            ->when($search !== '', fn ($query) => $query->where(fn ($inner) => $inner
                ->where('name', 'like', $like)->orWhere('apellidos', 'like', $like)
                ->orWhere('documento_identidad', 'like', $like)->orWhere('email', 'like', $like)->orWhere('cel', 'like', $like)))
            ->latest('id')
            ->paginate(25, ['*'], 'page')
            ->withQueryString();

        $accounts = CustomerAccount::query()
            ->withCount('orders')
            ->when($search !== '', fn ($query) => $query->where(fn ($inner) => $inner
                ->where('name', 'like', $like)->orWhere('apellidos', 'like', $like)->orWhere('email', 'like', $like)->orWhere('phone', 'like', $like)))
            ->latest('id')
            ->paginate(25, ['*'], 'page')
            ->withQueryString();

        return view('admin.clientes.index', [
            'tab' => $tab,
            'search' => $search,
            'buyers' => $tab === 'compradores' ? $buyers : null,
            'accounts' => $tab === 'cuentas' ? $accounts : null,
            'buyerCount' => Cliente::query()->count(),
            'accountCount' => CustomerAccount::query()->count(),
        ]);
    }

    public function show(Cliente $cliente): View
    {
        $orders = $cliente->orders()->with('statusOrder')->latest('id')->get();

        return view('admin.clientes.show', [
            'cliente' => $cliente->load(['tipoDocumento']),
            'orders' => $orders,
            'total' => (float) $orders->sum('precio_total'),
            'account' => $cliente->email ? CustomerAccount::query()->where('email', $cliente->email)->first() : null,
        ]);
    }
}

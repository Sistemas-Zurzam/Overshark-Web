<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Mi cuenta: resumen e historial de pedidos. Todo se consulta desde la cuenta autenticada ($request->user('customer')),
 * así que un pedido ajeno simplemente no existe para ella (404), aunque cambie el número en la URL.
 */
class AccountController extends Controller
{
    public function dashboard(Request $request): View
    {
        $account = $request->user('customer');

        return view('web.account.dashboard', [
            'account' => $account,
            'ordersCount' => $account->orders()->count(),
            'recentOrders' => $account->orders()->with('statusOrder')->latest()->limit(3)->get(),
            'addressesCount' => $account->addresses()->count(),
        ]);
    }

    public function orders(Request $request): View
    {
        return view('web.account.orders', [
            'orders' => $request->user('customer')->orders()->with('statusOrder')->latest()->paginate(10),
        ]);
    }

    public function showOrder(Request $request, int $order): View
    {
        return view('web.account.order-show', [
            'order' => $request->user('customer')->orders()
                ->with(['statusOrder', 'distrito.provincia.departamento'])
                ->findOrFail($order),
        ]);
    }
}

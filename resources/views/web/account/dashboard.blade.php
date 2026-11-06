@extends('web.account.layout')

@section('title', 'Mi cuenta | Overshark')
@section('heading', 'Mi cuenta')

@section('account')
    <div class="grid gap-4 sm:grid-cols-3">
        <a href="{{ route('customer.orders') }}" class="os-focus border border-[#dae0e5] p-5 transition hover:border-[#0b1015]">
            <p class="text-[13px] font-bold text-[#65717c]">Pedidos</p>
            <p class="mt-1 text-[30px] font-extrabold leading-none">{{ $ordersCount }}</p>
            <p class="mt-3 text-[13px] font-bold text-[#2467cf]">Ver historial →</p>
        </a>
        <a href="{{ route('customer.addresses') }}" class="os-focus border border-[#dae0e5] p-5 transition hover:border-[#0b1015]">
            <p class="text-[13px] font-bold text-[#65717c]">Direcciones</p>
            <p class="mt-1 text-[30px] font-extrabold leading-none">{{ $addressesCount }}</p>
            <p class="mt-3 text-[13px] font-bold text-[#2467cf]">Administrar →</p>
        </a>
        <a href="{{ route('customer.profile') }}" class="os-focus border border-[#dae0e5] p-5 transition hover:border-[#0b1015]">
            <p class="text-[13px] font-bold text-[#65717c]">Perfil y seguridad</p>
            <p class="mt-1 truncate text-[16px] font-extrabold leading-[1.9]">{{ $account->email }}</p>
            <p class="mt-3 text-[13px] font-bold text-[#2467cf]">Editar →</p>
        </a>
    </div>

    <section class="mt-8" aria-labelledby="last-orders">
        <div class="flex items-baseline justify-between gap-3">
            <h2 id="last-orders" class="text-[20px] font-extrabold">{{ $recentOrders->count() > 1 ? 'Tus últimos pedidos' : 'Tu último pedido' }}</h2>
            @if ($ordersCount > 3)<a href="{{ route('customer.orders') }}" class="text-[13px] font-bold text-[#2467cf] hover:underline">Ver todos</a>@endif
        </div>

        @forelse ($recentOrders as $order)
            @include('web.account.partials.order-row', ['order' => $order])
        @empty
            <div class="mt-4 border border-dashed border-[#dae0e5] p-8 text-center">
                <p class="text-[16px] font-extrabold">Aún no tienes pedidos en tu cuenta</p>
                <p class="mt-1 text-[14px] text-[#65717c]">Los pedidos que hagas con tu cuenta iniciada aparecerán aquí.</p>
                <a href="{{ route('web.products.search') }}" class="os-checkout-btn os-focus mt-4 !inline-flex w-full sm:w-[240px]">IR A LA TIENDA</a>
            </div>
        @endforelse
    </section>
@endsection

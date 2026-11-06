@extends('web.account.layout')

@section('title', 'Mis pedidos | Overshark')
@section('heading', 'Mis pedidos')

@section('account')
    @forelse ($orders as $order)
        @include('web.account.partials.order-row', ['order' => $order])
    @empty
        <div class="border border-dashed border-[#dae0e5] p-8 text-center">
            <p class="text-[16px] font-extrabold">Aún no tienes pedidos en tu cuenta</p>
            <p class="mt-1 text-[14px] text-[#65717c]">Los pedidos que hagas con tu cuenta iniciada aparecerán aquí. Los que hiciste como invitado no se agregan solos.</p>
            <a href="{{ route('web.products.search') }}" class="os-checkout-btn os-focus mt-4 !inline-flex w-full sm:w-[240px]">IR A LA TIENDA</a>
        </div>
    @endforelse

    @if ($orders->hasPages())
        <div class="mt-6">{{ $orders->links() }}</div>
    @endif
@endsection

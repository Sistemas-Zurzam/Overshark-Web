{{-- Footer de Overshark Girls. Va dentro de @section('footer'). --}}
@php
    $catalogUrl = route('web.girls.products');
    $brands = \App\Models\Brand::query()->active()->orderBy('sort_order')->orderBy('name')->get();
@endphp
<footer id="contacto" class="gl-foot">
    <div class="gl-wrap">
        <div class="gl-foot__cols">
            <div>
                <h2>Comprar</h2>
                <ul>
                    @forelse ($brands as $footerBrand)
                        <li><a href="{{ route('web.brands.show', $footerBrand) }}">{{ $footerBrand->name }}</a></li>
                    @empty
                        <li><a href="{{ $catalogUrl }}">Todos los productos</a></li>
                    @endforelse
                    <li><button type="button" data-combos-toggle aria-expanded="false" aria-controls="combos-menu">Combos</button></li>
                </ul>
            </div>
            <div>
                <h2>Ayuda</h2>
                <ul>
                    <li>Envíos</li>
                    <li>Cambios y devoluciones</li>
                    <li>Métodos de pago</li>
                    <li><a href="{{ route('web.claims.create') }}">Libro de Reclamaciones</a></li>
                </ul>
            </div>
            <div>
                <h2>Contacto</h2>
                <ul>
                    <li>Correo: ventas@overshark.com</li>
                    <li>Horario de atención: 9am a 6pm L-V</li>
                </ul>
            </div>
            <div>
                <h2>Envíos a todo el Perú</h2>
                <ul>
                    <li>Lima: delivery o agencia</li>
                    <li>Provincia: agencia</li>
                    <li>S/ 14 costo único</li>
                </ul>
            </div>
        </div>
        <div class="gl-foot__base">
            <span>© {{ date('Y') }} Overshark. Todos los derechos reservados.</span>
            <a href="{{ route('web.home', ['linea' => 'hombre']) }}">Ver colección hombre</a>
        </div>
    </div>
</footer>

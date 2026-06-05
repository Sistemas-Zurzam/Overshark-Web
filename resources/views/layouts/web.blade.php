@php
    $brand = \App\Models\BrandSetting::current();
    $menuCombos = \App\Models\Admin\Combo::query()
        ->where('status', true)
        ->whereNotNull('imagen')
        ->latest()
        ->get();
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Overshark, tienda online">
    <title>@yield('title', 'Overshark')</title>
    @if ($brand->iconUrl())
        <link rel="icon" href="{{ $brand->iconUrl() }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-white antialiased">
    <header class="fixed inset-x-0 top-0 z-50 border-b border-slate-200 bg-white/95 text-slate-950 shadow-sm backdrop-blur">
        <div class="relative mx-auto flex h-[84px] max-w-[1440px] items-center justify-between px-5 lg:px-12">
            <div class="flex items-center gap-8">
                <button type="button" data-combos-toggle class="combo-trigger group hidden text-base font-semibold sm:flex" aria-expanded="false" aria-controls="combos-menu">
                    <svg data-combo-flame class="combo-fire-icon" xmlns="http://www.w3.org/2000/svg" width="23" height="22" viewBox="0 0 23 22" fill="none" aria-hidden="true">
                        <path d="M3.74916 13.116C3.77168 12.5016 3.80963 12.5091 3.92464 11.9465C4.09258 11.1251 4.27104 10.6013 4.68849 9.8659C4.76518 9.73086 4.84914 9.47726 4.93534 9.32106C5.14305 8.94355 5.36816 8.58451 5.60863 8.22752C5.79795 7.94647 5.95202 7.62944 6.14497 7.3515C6.54383 6.77691 6.97596 6.28099 7.32431 5.67197C7.52524 5.32068 7.84118 4.96557 8.03221 4.59511C8.30289 4.12997 8.54694 3.65534 8.78857 3.1696C9.00966 2.73301 9.07885 2.22221 9.1826 1.74749C9.2266 1.54624 9.17702 1.29912 9.24732 1.07828C9.38406 0.648729 9.63513 0.483011 10.0832 0.505988C10.4374 0.524152 10.6265 0.748718 10.8414 0.998789C12.5795 3.0167 13.8277 5.40896 14.4887 7.98885C14.5263 8.14124 14.6288 8.42573 14.6497 8.55846C14.7155 8.97617 14.694 9.42625 14.7518 9.85118C15.3986 9.32083 15.8828 8.61886 16.1487 7.82585C16.2197 7.61943 16.324 7.18986 16.4231 7.02063C16.9783 6.73549 17.4723 6.57591 17.8367 7.28503C17.9973 7.59758 18.0879 7.94372 18.2171 8.26993C18.5938 9.42625 19.0655 10.573 19.2085 11.7883C19.2256 11.9342 19.2892 12.127 19.3204 12.2774C19.4183 12.7486 19.3955 13.2648 19.4221 13.7457C19.4175 14.2282 19.3826 14.8076 19.3873 15.2645C19.382 15.5019 19.2278 15.9199 19.1609 16.1865C19.0701 16.6086 19.0801 16.6247 18.9125 16.9978C18.8443 17.1467 18.7664 17.3622 18.6904 17.4936C18.6702 17.5506 18.627 17.6627 18.6139 17.7166L18.4726 17.9368C18.3688 18.0775 18.3314 18.2235 18.2277 18.3677C17.968 18.6468 17.8128 19.1076 17.4655 19.3958C17.1062 19.9988 16.8531 20.0644 16.4197 20.4828C16.2974 20.556 16.2338 20.5907 16.1463 20.7088C16.0774 20.7724 16.0128 20.7759 15.9611 20.8187C15.5926 20.9695 15.4447 20.9833 15.0859 20.814C14.9918 20.7614 14.9662 20.7573 14.9151 20.6599C14.818 20.4848 14.7616 20.3637 14.6826 20.1793L14.6442 20.0612C14.5337 19.8086 14.5865 19.7202 14.3866 19.4344L14.377 19.4176L14.2468 19.0937C14.2381 19.0605 14.1703 18.9613 14.1477 18.9261L13.9867 18.6706C13.8829 18.5613 13.6928 18.2823 13.5993 18.1484C13.1918 17.4013 11.882 16.4866 11.3825 15.7537C11.3385 15.7112 11.0751 15.3321 11.0227 15.2585L10.9425 15.0699C10.7576 14.8026 10.6827 14.5699 10.6228 14.2507C10.547 13.6216 10.5375 13.3672 10.6536 12.7269L10.6596 12.7025C10.679 12.6287 10.7107 12.5483 10.684 12.4795C10.5192 12.3598 10.1769 12.6618 10.0074 12.7432C9.91662 12.8225 9.83506 12.8949 9.73747 12.9665C9.6468 13.0439 9.61106 13.0913 9.53841 13.1828C9.45069 13.2313 9.35608 13.3641 9.28935 13.4463C9.19933 13.5038 9.10333 13.6874 9.04369 13.7887C8.92902 13.9413 8.8537 14.1906 8.73917 14.3768C8.662 14.5274 8.61574 14.6236 8.55619 14.7813C8.44729 15.0645 8.44019 16.2887 8.55845 16.5919C8.58803 16.7071 8.67607 16.9167 8.73451 17.0217C8.84721 17.3325 9.21942 18.074 9.42649 18.3083C9.54547 18.4749 9.66653 18.6526 9.80348 18.8037C9.92963 18.9509 10.1572 19.232 10.2953 19.3487C10.5676 19.5556 10.7242 19.8229 10.8586 20.1275C10.745 20.5337 10.5109 20.9402 10.0276 20.9209C8.59661 20.8636 7.10797 19.7715 6.07433 18.851C5.04645 17.9357 3.81713 16.0235 3.74023 14.6415C3.76478 14.4337 3.74206 14.1009 3.7422 13.8822C3.74236 13.6284 3.75387 13.3674 3.74916 13.116Z" fill="#0078D7"/>
                    </svg>
                    Combos
                </button>

                <nav class="hidden items-center gap-8 text-base font-medium lg:flex" aria-label="Navegación principal">
                    <a href="#productos" class="flex items-center gap-1.5 transition hover:text-cyan-600">
                        Productos
                        <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m7 10 5 5 5-5"/></svg>
                    </a>
                    <a href="#categorias" class="transition hover:text-cyan-600">Categorías</a>
                    <a href="#contacto" class="transition hover:text-cyan-600">Nosotros</a>
                </nav>
            </div>

            <a href="{{ route('web.home') }}" class="absolute left-1/2 flex h-[76px] w-56 -translate-x-1/2 items-center justify-center overflow-hidden text-2xl font-black tracking-[-0.07em] sm:w-72 sm:text-3xl" aria-label="Overshark inicio">
                @if ($brand->logoUrl())
                    <img src="{{ $brand->logoUrl() }}" alt="Overshark" class="h-16 w-full scale-125 object-contain sm:h-20 sm:scale-150">
                @else
                    OVER<span class="text-cyan-600">SHARK</span>
                @endif
            </a>

            <div class="flex items-center gap-1 sm:gap-3">
                <a href="#productos" class="grid h-10 w-10 place-items-center rounded-full transition hover:bg-slate-100" aria-label="Buscar productos">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4 4"/></svg>
                </a>
                <a href="{{ route('admin.dashboard') }}" class="hidden h-10 w-10 place-items-center rounded-full transition hover:bg-slate-100 sm:grid" aria-label="Mi cuenta">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="12" cy="7" r="3.5"/><path d="M5.5 21c.4-5 2.6-7.5 6.5-7.5s6.1 2.5 6.5 7.5"/></svg>
                </a>
                <a href="#productos" class="hidden h-10 w-10 place-items-center rounded-full transition hover:bg-slate-100 sm:grid" aria-label="Bolsa de compras">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M5 8h14l1 13H4Z"/><path d="M9 9V6a3 3 0 0 1 6 0v3"/></svg>
                </a>
                <button type="button" data-web-menu-toggle class="grid h-10 w-10 place-items-center rounded-full transition hover:bg-slate-100 lg:hidden" aria-label="Abrir menú" aria-expanded="false">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                </button>
            </div>
        </div>

        <div id="combos-menu" data-combos-menu data-state="closed" class="combos-panel absolute left-0 right-0 top-full border-t border-slate-100 bg-white shadow-2xl">
            <div class="mx-auto grid max-w-[1440px] gap-4 px-5 py-6 sm:grid-cols-2 lg:grid-cols-3 lg:px-12">
                @forelse ($menuCombos as $combo)
                    <a href="{{ $combo->url }}" class="combo-card group relative min-h-48 overflow-hidden rounded-2xl border border-slate-200 bg-slate-950">
                        <img src="{{ $combo->imageUrl() }}" alt="{{ $combo->name }}" class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-110">
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/25 to-transparent"></div>
                        <div class="relative flex h-full min-h-48 items-end p-5">
                            <div>
                                <span class="text-xs font-black uppercase tracking-[0.18em] text-cyan-300">Ver combo</span>
                                <h2 class="mt-1 text-xl font-black text-white">{{ $combo->name }}</h2>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="col-span-full rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-5 py-10 text-center text-sm font-semibold text-slate-500">
                        Aún no hay combos activos.
                    </div>
                @endforelse
            </div>
        </div>

        <nav data-web-menu class="hidden border-t border-slate-100 bg-white px-5 py-4 text-sm font-semibold lg:hidden" aria-label="Navegación móvil">
            <div class="mx-auto flex max-w-[1440px] flex-col gap-1">
                <button type="button" data-combos-toggle class="flex items-center justify-between rounded-lg px-3 py-3 text-left hover:bg-slate-100" aria-expanded="false" aria-controls="combos-menu">
                    Combos y productos
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m7 10 5 5 5-5"/></svg>
                </button>
                <a href="#categorias" class="rounded-lg px-3 py-3 hover:bg-slate-100">Categorías</a>
                <a href="#contacto" class="rounded-lg px-3 py-3 hover:bg-slate-100">Nosotros</a>
                <a href="{{ route('admin.dashboard') }}" class="rounded-lg px-3 py-3 hover:bg-slate-100">Mi cuenta</a>
            </div>
        </nav>
    </header>

    <main class="pt-[84px]">@yield('content')</main>

    <footer id="contacto" class="border-t border-slate-200 bg-gradient-to-r from-slate-50 via-white to-blue-50 text-slate-900">
        <div class="border-b border-slate-200 px-5 py-7">
            <a href="{{ route('web.home') }}" class="mx-auto flex w-fit items-center justify-center" aria-label="Overshark inicio">
                @if ($brand->logoUrl())
                    <img src="{{ $brand->logoUrl() }}" alt="Overshark" class="h-16 w-56 object-contain sm:w-64">
                @else
                    <span class="text-3xl font-black tracking-[-0.07em]">OVER<span class="text-cyan-600">SHARK</span></span>
                @endif
            </a>
        </div>

        <div class="mx-auto grid max-w-7xl gap-10 px-6 py-12 sm:grid-cols-2 lg:grid-cols-4 lg:px-8">
            <section>
                <h2 class="text-lg font-black">Comprar</h2>
                <nav class="mt-5 flex flex-col gap-3 text-sm text-slate-600">
                    <a href="#productos" class="transition hover:text-cyan-700">Polos</a>
                    <a href="#productos" class="transition hover:text-cyan-700">Medias</a>
                    <a href="#combos" class="flex items-center gap-2 transition hover:text-cyan-700">
                        Combos
                        <svg class="h-4 w-4 text-red-500" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M13.5 1.8c.7 4.4-2.2 5.8-2.2 8.7 0 1.2.7 2.1 1.7 2.1 1.6 0 2.5-1.5 2.1-3.5 2.6 2 4 4.4 4 7.1a7.1 7.1 0 0 1-14.2 0c0-3.8 2-7.3 5.8-10.5-.2 2.6.5 4.1 1.5 4.1 1.4 0 2.3-2.7 1.3-8Z"/></svg>
                    </a>
                    <a href="#productos" class="flex items-center gap-2 transition hover:text-cyan-700">
                        Para ellas
                        <svg class="h-4 w-4 text-pink-500" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 21s-8-4.8-8-11a4.5 4.5 0 0 1 8-2.8A4.5 4.5 0 0 1 20 10c0 6.2-8 11-8 11Z"/></svg>
                    </a>
                </nav>
            </section>

            <section>
                <h2 class="text-lg font-black">Ayuda</h2>
                <nav class="mt-5 flex flex-col gap-3 text-sm text-slate-600">
                    <a href="#contacto" class="transition hover:text-cyan-700">Envíos</a>
                    <a href="#contacto" class="transition hover:text-cyan-700">Cambios y devoluciones</a>
                    <a href="#contacto" class="transition hover:text-cyan-700">Métodos de pago</a>
                </nav>
            </section>

            <section>
                <h2 class="text-lg font-black">Contacto</h2>
                <div class="mt-5 space-y-5 text-sm leading-6 text-slate-600">
                    <p><span class="block font-bold text-slate-800">Correo:</span> ventas@overshark.com</p>
                    <p><span class="block font-bold text-slate-800">Horario de atención:</span> 9am a 6pm L-V</p>
                </div>
            </section>

            <section>
                <h2 class="text-lg font-black">Envíos a todo el Perú</h2>
                <div class="mt-5 space-y-2 text-sm text-slate-500">
                    <p>Lima: delivery o agencia</p>
                    <p>Provincia: agencia</p>
                    <p class="mt-3 w-fit rounded-full bg-blue-100 px-4 py-1.5 font-bold text-blue-700">S/14 costo único</p>
                </div>
                <div class="mt-5 flex items-center gap-3 text-slate-500">
                    <svg class="h-14 w-14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><path d="M3 5.5c3.2-.8 5.8-.3 9 1.5v12c-3.2-1.8-5.8-2.3-9-1.5ZM21 5.5c-3.2-.8-5.8-.3-9 1.5v12c3.2-1.8 5.8-2.3 9-1.5Z"/><path d="M12 7v12"/></svg>
                    <span class="text-xs font-black uppercase leading-tight">Libro de<br>reclamaciones</span>
                </div>
            </section>
        </div>

        <div class="border-t border-slate-200">
            <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-5 px-6 py-6 text-sm text-slate-500 sm:flex-row lg:px-8">
                <div class="flex items-center gap-4">
                    @foreach ([
                        ['Facebook', 'M7 9h3V7c0-2.8 1.7-4.5 4.4-4.5 1.2 0 2.3.1 2.6.3v3h-1.8c-1.4 0-1.7.7-1.7 1.7V9H18l-.6 3h-2.9v9H10v-9H7Z'],
                        ['Instagram', 'M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5Zm5 5a5 5 0 1 0 0 10 5 5 0 0 0 0-10Zm6-1.2a1.2 1.2 0 1 0 0 2.4 1.2 1.2 0 0 0 0-2.4Z'],
                        ['WhatsApp', 'M20.5 3.5A10 10 0 0 0 4.8 15.6L3.5 21l5.6-1.3A10 10 0 1 0 20.5 3.5Zm-4 13.2c-.4.9-2 1.7-2.8 1.8-.8.1-1.8.1-2.9-.3-2.5-.9-5.4-3.3-6.3-6-.3-.8-.1-1.8.4-2.3.4-.4.8-.5 1.2-.5h.6c.2 0 .5 0 .7.5l1 2.3c.1.3.1.6-.1.8l-.8 1c-.2.2-.2.4 0 .7.7 1.2 1.7 2.1 2.9 2.7.3.2.6.1.8-.1l1-1.2c.2-.3.5-.3.8-.2l2.4 1.1c.3.2.6.3.7.5.2.2.2.8-.1 1.2Z'],
                    ] as [$social, $path])
                        <a href="#" class="grid h-9 w-9 place-items-center rounded-full bg-slate-600 text-white transition hover:-translate-y-0.5 hover:bg-cyan-600" aria-label="{{ $social }}">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="{{ $path }}"/></svg>
                        </a>
                    @endforeach
                </div>
                <p>© {{ date('Y') }} Overshark. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>
</body>
</html>

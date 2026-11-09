{{-- Un modal por documento legal: se abre con cualquier enlace [data-legal-open="clave"] (resources/js/legal-modal.js).
     Los enlaces siguen apuntando a la página del documento, que es lo que abre el navegador sin JavaScript. --}}
@foreach (\App\Support\LegalDocuments::all() as $key => $doc)
    {{-- La página de un documento ya muestra su texto: sin modal duplicado. --}}
    @continue(request()->routeIs($doc['route']))

    <dialog data-legal-modal="{{ $key }}" aria-labelledby="legal-modal-title-{{ $key }}" class="m-auto w-[calc(100%-24px)] max-w-[820px] overflow-hidden bg-white p-0 text-[#071226] shadow-2xl backdrop:bg-[#071226]/70 sm:w-full">
        <div class="flex max-h-[88vh] flex-col font-['Manrope',sans-serif]">
            <div class="flex items-start justify-between gap-4 border-b border-[#dae0e5] px-5 py-4 sm:px-7">
                <div>
                    <p class="text-[11px] font-extrabold uppercase leading-[1.55]">Información legal · Overshark Hombre</p>
                    <h2 id="legal-modal-title-{{ $key }}" class="text-[22px] font-extrabold leading-[1.3] sm:text-[26px]">{{ $doc['title'] }}</h2>
                </div>
                <button type="button" data-legal-close class="os-focus grid h-10 w-10 shrink-0 place-items-center hover:bg-[#f3f4f6]" aria-label="Cerrar">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                </button>
            </div>

            <div class="flex flex-col gap-6 overflow-y-auto px-5 py-6 sm:px-7" tabindex="0" data-legal-scroll>
                @include('web.partials.legal-content', ['document' => $key])
            </div>

            <div class="flex flex-col gap-2 border-t border-[#dae0e5] bg-white px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-7">
                <a href="{{ route($doc['route']) }}" class="os-focus text-[13px] font-bold underline underline-offset-4">Abrir en una página</a>
                <button type="button" data-legal-close class="os-focus w-full bg-[#071226] px-8 py-3 text-[13px] font-extrabold uppercase tracking-[1.2px] text-white hover:bg-[#0078d7] sm:w-auto">Entendido</button>
            </div>
        </div>
    </dialog>
@endforeach

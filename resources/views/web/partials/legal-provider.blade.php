{{-- Bloque "Proveedor responsable" (Admin → Contenido de la tienda → Datos del proveedor; «[por confirmar]» si faltan):
     lo comparten los documentos legales y el Libro de Reclamaciones. --}}
<section class="flex flex-col gap-[6px] bg-[#f5f6f8] p-5" aria-label="Proveedor responsable">
    <h2 class="text-[15px] font-extrabold leading-[1.55]">Proveedor responsable</h2>
    <dl class="text-[13px] leading-[1.55] text-[#252932]">
        @foreach (\App\Support\SiteContent::provider() as $label => $value)
            <div class="flex gap-1"><dt>{{ $label }}:</dt><dd>{{ $value }}</dd></div>
        @endforeach
    </dl>
</section>

{{-- Cuerpo de un documento legal (App\Support\LegalDocuments): lo comparten su página y su modal. $document = clave.
     Sin ids: la página y el modal pueden convivir. --}}
@php($doc = \App\Support\LegalDocuments::find($document))

@include('web.partials.legal-provider')

@foreach ($doc['sections'] as [$heading, $text])
    <section class="flex flex-col gap-2 leading-[1.55]">
        <h2 class="text-[19px] font-extrabold">{{ $heading }}</h2>
        <p class="text-[15px] text-[#252932]">{{ $text }}</p>
    </section>
@endforeach

@if ($note = \App\Support\LegalDocuments::note())
    <p class="text-[12px] leading-[1.55] text-[#687080]">{{ $note }}</p>
@endif

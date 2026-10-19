{{-- Foto editable de Nosotros. Variables: $key, $image (label, url, uploaded, file). --}}
<div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
    <p class="text-xs font-black uppercase tracking-wide text-slate-500">{{ $image['label'] }}</p>
    <div class="mt-2 grid aspect-[4/3] place-items-center overflow-hidden rounded-xl bg-slate-200">
        @if ($image['url'])
            <img src="{{ $image['url'] }}" alt="" class="h-full w-full object-cover grayscale">
        @else
            <span class="px-3 text-center text-xs font-semibold text-slate-500">Sin foto<br><code class="text-[10px]">{{ $image['file'] }}</code></span>
        @endif
    </div>
    <input type="file" name="images[{{ $key }}]" accept=".png,.jpg,.jpeg,.webp" aria-label="Subir foto: {{ $image['label'] }}" class="mt-2 block w-full text-xs file:mr-2 file:rounded-lg file:border-0 file:bg-slate-950 file:px-3 file:py-1.5 file:font-bold file:text-white hover:file:bg-cyan-600">
    @if ($image['uploaded'])
        <label class="mt-2 flex items-center gap-2 text-xs font-semibold text-slate-600">
            <input type="checkbox" name="remove_images[{{ $key }}]" value="1" class="rounded border-slate-300 text-red-600"> Quitar foto subida
        </label>
    @endif
    @error("images.{$key}")<span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span>@enderror
</div>

<?php

namespace App\Http\Controllers;

use App\Models\Admin\Categoria;
use App\Models\Admin\Producto;
use App\Services\OutfitAi\OutfitAiGenerator;
use App\Services\OutfitAi\OutfitAiResult;
use App\Services\OutfitPhotoStore;
use App\Support\OutfitCatalog;
use App\Support\SessionCart;
use App\Support\StorefrontCategories;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Probador "Arma tu outfit con IA". Todo el estado (foto, modelo generado) vive en la sesión del
 * visitante: las URLs nunca llevan identificadores, así que otra sesión no puede consultar sus archivos.
 */
class OutfitBuilderController extends Controller
{
    private const MAX_EXTRAS = 3;

    public function __construct(
        private readonly OutfitAiGenerator $ai,
        private readonly OutfitPhotoStore $photos,
    ) {}

    public function show(Request $request): View
    {
        $lines = ['hombre', 'mujer'];

        if (in_array($request->query('linea'), $lines, true)) {
            session(['storefront.line' => $request->query('linea')]);
        }

        $line = in_array(session('storefront.line'), $lines, true) ? session('storefront.line') : 'hombre';
        $products = OutfitCatalog::products();
        $categories = collect(StorefrontCategories::all())
            ->filter(fn (array $category, string $slug): bool => $products->contains('category', $slug))
            ->map(fn (array $category): string => $category['label']);

        return view('web.outfit-builder', [
            'line' => $line,
            'products' => $products,
            'categories' => $categories,
            'state' => $this->state(),
            'aiAvailable' => $this->ai->isAvailable(),
            'aiProvider' => $this->ai->providerName(),
            'multiView' => $this->ai->isAvailable() && $this->ai->supportsMultiView(),
            'retentionHours' => OutfitPhotoStore::RETENTION_HOURS,
        ]);
    }

    public function storePhoto(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:10240',
                'dimensions:min_width=256,min_height=256,max_width=8000,max_height=8000',
            ],
        ], [
            'photo.required' => 'Elige una foto.',
            'photo.image' => 'El archivo debe ser una imagen JPEG, PNG o WebP.',
            'photo.mimes' => 'El archivo debe ser una imagen JPEG, PNG o WebP.',
            'photo.mimetypes' => 'El archivo debe ser una imagen JPEG, PNG o WebP.',
            'photo.max' => 'La foto no puede superar los 10 MB.',
            'photo.dimensions' => 'La foto debe medir entre 256 y 8000 píxeles por lado.',
        ]);

        $this->forgetModel();
        $this->photos->delete(session('outfit.photo'));
        session(['outfit.photo' => $this->photos->store($request->file('photo'))]);

        return response()->json($this->state());
    }

    public function showPhoto(): BinaryFileResponse
    {
        return $this->privateFile(session('outfit.photo'));
    }

    public function destroyPhoto(): JsonResponse
    {
        $this->forgetModel();
        $this->photos->delete(session('outfit.photo'));
        session()->forget(['outfit.photo', 'outfit.consent']);

        return response()->json($this->state());
    }

    public function generateModel(Request $request): JsonResponse
    {
        if (! $this->photos->exists(session('outfit.photo'))) {
            return response()->json(['message' => 'Primero sube o toma una foto.', ...$this->state()], 422);
        }

        if (! $this->ai->isAvailable()) {
            return $this->unavailable();
        }

        $request->validate(['consent' => ['accepted']], [
            'consent.accepted' => 'Debes aceptar el envío de tu foto para generar el modelo.',
        ]);

        session(['outfit.consent' => true]);
        $this->forgetModel();
        $this->apply($this->ai->generateModel(session('outfit.photo')), []);

        return response()->json($this->state());
    }

    public function modelStatus(): JsonResponse
    {
        $job = session('outfit.model.job');

        if ($job && $this->ai->isAvailable()) {
            $this->apply($this->ai->status($job['reference']), $job['variant_ids']);
        }

        return response()->json($this->state());
    }

    public function showModel(): BinaryFileResponse
    {
        return $this->privateFile(session('outfit.model.image'));
    }

    public function tryOn(Request $request): JsonResponse
    {
        $variants = $this->selectedVariants($request);

        if (! $this->ai->isAvailable()) {
            return $this->unavailable();
        }

        $photo = session('outfit.photo');

        if (! session('outfit.consent') || ! $this->photos->exists($photo) || ! $this->photos->exists(session('outfit.model.image'))) {
            return response()->json(['message' => 'Primero genera tu modelo.', ...$this->state()], 422);
        }

        if (session('outfit.model.job')) {
            return response()->json(['message' => 'Ya estamos probando un outfit. Espera a que termine.', ...$this->state()], 409);
        }

        // Siempre se parte de la foto original para no acumular prendas de pruebas anteriores.
        $variantIds = $variants->filter(fn ($variant) => $variant instanceof Producto)->pluck('id')->all();
        $this->apply($this->ai->tryOn($photo, $variantIds), $variantIds);

        if (session('outfit.model.message') && ! session('outfit.model.job')) {
            return response()->json(['message' => session('outfit.model.message'), ...$this->state()], 422);
        }

        return response()->json($this->state());
    }

    public function summary(Request $request): JsonResponse
    {
        return response()->json($this->summarize($this->selectedVariants($request)));
    }

    public function addToCart(Request $request): JsonResponse
    {
        $selection = $this->selectedVariants($request);
        $summary = $this->summarize($selection);

        if (! $summary['all_available']) {
            return response()->json([
                'message' => 'Algunas prendas ya no están disponibles. Revisa el resumen.',
                ...$summary,
            ], 409);
        }

        foreach ($selection as $variant) {
            SessionCart::addVariant($variant, 1);
        }

        return response()->json([
            'message' => 'Outfit agregado al carrito.',
            'redirect' => route('web.cart.index'),
        ]);
    }

    /**
     * Valida la selección enviada y comprueba que sea un outfit coherente.
     */
    private function selectedVariants(Request $request): Collection
    {
        $validated = $request->validate([
            'variant_ids' => ['required', 'array', 'min:1', 'max:'.(2 + self::MAX_EXTRAS)],
            'variant_ids.*' => ['required', 'integer', 'distinct'],
        ], [
            'variant_ids.required' => 'Elige al menos una prenda.',
            'variant_ids.max' => 'Un outfit admite una prenda superior, una inferior y hasta '.self::MAX_EXTRAS.' complementos.',
        ]);

        $variants = Producto::query()->whereIn('id', $validated['variant_ids'])->get()->keyBy('id');
        $categoryNames = Categoria::query()->whereIn('id', $variants->pluck('categoria_id')->filter())->pluck('name', 'id');
        $slots = $variants->map(fn (Producto $variant): string => StorefrontCategories::slotFor(
            StorefrontCategories::slugFor($variant->name, $categoryNames->get($variant->categoria_id)),
        ));

        if ($slots->filter(fn (string $slot) => $slot === 'top')->count() > 1
            || $slots->filter(fn (string $slot) => $slot === 'bottom')->count() > 1) {
            throw ValidationException::withMessages([
                'variant_ids' => 'Elige solo una prenda superior y una inferior.',
            ]);
        }

        return collect($validated['variant_ids'])->map(fn (int $id) => $variants->get($id) ?? $id);
    }

    /**
     * Revalida precio, stock y existencia de cada variante contra la base de datos actual.
     */
    private function summarize(Collection $selection): array
    {
        $lines = $selection->map(function (Producto|int $variant): array {
            if (! $variant instanceof Producto) {
                return ['variant_id' => $variant, 'name' => 'Prenda', 'available' => false, 'reason' => 'Este producto ya no existe en el catálogo.'];
            }

            $reason = match (true) {
                (float) $variant->price <= 0 => 'Precio por confirmar: esta prenda todavía no se puede comprar.',
                (int) $variant->stock <= 0 => 'Sin stock en esta talla y color.',
                SessionCart::quantityOf($variant->id) >= (int) $variant->stock => 'Ya tienes en tu carrito todo el stock disponible.',
                default => null,
            };

            return [
                'variant_id' => $variant->id,
                'name' => Str::title(mb_strtolower($variant->name)),
                'color' => $variant->color,
                'size' => $variant->talla,
                'price' => (float) $variant->price,
                'available' => $reason === null,
                'reason' => $reason,
            ];
        })->values();

        return [
            'lines' => $lines->all(),
            'total' => round($lines->where('available', true)->sum('price'), 2),
            'all_available' => $lines->every(fn (array $line): bool => $line['available']),
        ];
    }

    /**
     * Aplica el resultado del proveedor sobre el modelo de la sesión. Mientras hay un trabajo en curso
     * se conserva la imagen anterior; si falla, se mantiene y solo se informa el error.
     */
    private function apply(OutfitAiResult $result, array $variantIds): void
    {
        $model = session('outfit.model', []);

        if ($result->status === OutfitAiResult::COMPLETED && ! $this->photos->exists($result->imagePath)) {
            $result = OutfitAiResult::failed('El proveedor no devolvió una imagen válida.');
        }

        $model = match ($result->status) {
            OutfitAiResult::PROCESSING => [
                ...$model,
                'job' => ['reference' => $result->reference, 'variant_ids' => $variantIds],
                'message' => null,
            ],
            OutfitAiResult::COMPLETED => (function () use ($model, $result, $variantIds): array {
                if (($model['image'] ?? null) !== $result->imagePath) {
                    $this->photos->delete($model['image'] ?? null);
                }

                return ['status' => OutfitAiResult::COMPLETED, 'image' => $result->imagePath, 'variant_ids' => $variantIds, 'job' => null, 'message' => null];
            })(),
            default => [
                'status' => ($model['image'] ?? null) ? OutfitAiResult::COMPLETED : $result->status,
                'image' => $model['image'] ?? null,
                'variant_ids' => $model['variant_ids'] ?? [],
                'job' => null,
                'message' => $result->message,
            ],
        };

        session(['outfit.model' => $model]);
    }

    private function forgetModel(): void
    {
        $this->photos->delete(session('outfit.model.image'));
        session()->forget('outfit.model');
    }

    private function unavailable(): JsonResponse
    {
        return response()->json([
            'message' => OutfitAiResult::unavailable()->message,
            ...$this->state(),
        ], 503);
    }

    private function privateFile(?string $path): BinaryFileResponse
    {
        abort_unless($this->photos->exists($path), 404);

        // BinaryFileResponse se marca "public" por defecto: se fuerza privado para que ninguna caché compartida lo guarde.
        $response = response()->file($this->photos->absolutePath($path), ['X-Content-Type-Options' => 'nosniff']);
        $response->setPrivate();
        $response->setMaxAge(0);
        $response->headers->addCacheControlDirective('no-store');

        return $response;
    }

    /**
     * Estado público del probador para la sesión actual (sin rutas internas de archivos).
     */
    private function state(): array
    {
        $hasPhoto = $this->photos->exists(session('outfit.photo'));
        $model = session('outfit.model', []);
        $hasImage = $this->photos->exists($model['image'] ?? null);
        $status = ! empty($model['job']) ? OutfitAiResult::PROCESSING : ($model['status'] ?? null);

        return [
            'ai_available' => $this->ai->isAvailable(),
            'photo_url' => $hasPhoto ? route('web.outfit.photo.show', ['v' => substr(md5((string) session('outfit.photo')), 0, 8)]) : null,
            'model' => [
                'status' => $status === OutfitAiResult::COMPLETED && ! $hasImage ? null : $status,
                'image_url' => $hasImage ? route('web.outfit.model.show', ['v' => substr(md5((string) $model['image']), 0, 8)]) : null,
                'message' => $model['message'] ?? null,
                'variant_ids' => $hasImage ? ($model['variant_ids'] ?? []) : [],
            ],
        ];
    }
}

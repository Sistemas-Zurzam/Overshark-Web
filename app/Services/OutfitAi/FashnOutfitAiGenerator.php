<?php

namespace App\Services\OutfitAi;

use App\Models\Admin\Categoria;
use App\Models\Admin\Producto;
use App\Models\Admin\ProductoColorImage;
use App\Services\OutfitPhotoStore;
use App\Support\StorefrontCategories;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Probador virtual con FASHN (https://docs.fashn.ai/api-reference/tryon-v1-6).
 *
 * - El "modelo" es la propia foto del cliente: generateModel() solo la prepara, sin llamar a FASHN.
 * - tryOn() envía la foto y la prenda superior; si hay prenda inferior, al terminar se encadena una
 *   segunda prueba sobre el resultado. FASHN prueba una prenda por llamada (tops / bottoms).
 * - Las imágenes viajan como data URI y se pide return_base64: según FASHN, el historial guarda
 *   solo marcadores y el resultado está disponible 60 minutos. El resultado se copia al disco privado.
 * - El avance vive en caché (no en la sesión) bajo una referencia aleatoria; el navegador consulta
 *   el estado y cada consulta hace un GET corto a FASHN, sin colas ni peticiones largas.
 */
class FashnOutfitAiGenerator implements OutfitAiGenerator
{
    private const CACHE_PREFIX = 'outfit.fashn.';

    public function __construct(private readonly OutfitPhotoStore $photos) {}

    public function isAvailable(): bool
    {
        return filled(config('services.fashn.api_key'));
    }

    public function providerName(): ?string
    {
        return 'FASHN (fashn.ai)';
    }

    public function supportsMultiView(): bool
    {
        return false;
    }

    public function generateModel(string $photoPath): OutfitAiResult
    {
        $path = OutfitPhotoStore::DIRECTORY.'/results/'.Str::random(40).'.jpg';
        Storage::disk('local')->copy($photoPath, $path);

        return OutfitAiResult::completed($path);
    }

    public function tryOn(string $modelPath, array $variantIds): OutfitAiResult
    {
        $garments = $this->garmentsFor($variantIds);

        if ($garments === []) {
            return OutfitAiResult::failed('Elige una prenda superior o inferior: los complementos todavía no se pueden probar.');
        }

        $missing = collect($garments)->whereNull('image')->pluck('name');
        if ($missing->isNotEmpty()) {
            return OutfitAiResult::failed('Estas prendas aún no tienen foto en el catálogo y no se pueden probar: '.$missing->implode(', ').'.');
        }

        $personImage = $this->dataUri(Storage::disk('local')->get($modelPath), 'image/jpeg');

        return $this->submit($personImage, $garments);
    }

    public function status(string $reference): OutfitAiResult
    {
        $job = Cache::get(self::CACHE_PREFIX.$reference);

        if (! is_array($job)) {
            return OutfitAiResult::failed('La prueba expiró. Vuelve a pulsar «Probar outfit».');
        }

        try {
            $response = $this->client()->get('/status/'.$job['prediction_id'])->throw()->json();
        } catch (Throwable $e) {
            Log::warning('FASHN status falló', ['message' => $e->getMessage()]);

            return OutfitAiResult::processing($reference);
        }

        $status = $response['status'] ?? null;

        if (in_array($status, ['starting', 'in_queue', 'processing'], true)) {
            return OutfitAiResult::processing($reference);
        }

        Cache::forget(self::CACHE_PREFIX.$reference);

        if ($status !== 'completed' || empty($response['output'][0])) {
            return OutfitAiResult::failed($this->errorMessage($response['error'] ?? null));
        }

        $output = (string) $response['output'][0];

        // Quedan prendas por probar (p. ej. la inferior): se encadenan sobre este resultado.
        if ($job['remaining'] !== []) {
            return $this->submit($this->asDataUri($output), $job['remaining']);
        }

        return $this->storeResult($output);
    }

    /**
     * @param  list<array{name: string, category: string, image: ?string}>  $garments
     */
    private function submit(string $personImage, array $garments): OutfitAiResult
    {
        $garment = array_shift($garments);

        try {
            $response = $this->client()->post('/run', [
                'model_name' => config('services.fashn.model'),
                'inputs' => [
                    'model_image' => $personImage,
                    'garment_image' => $garment['image'],
                    'category' => $garment['category'],
                    'mode' => config('services.fashn.mode'),
                    'output_format' => 'jpeg',
                    'return_base64' => true,
                ],
            ])->json();
        } catch (Throwable $e) {
            Log::warning('FASHN run falló', ['message' => $e->getMessage()]);

            return OutfitAiResult::failed('No pudimos conectar con el probador. Inténtalo de nuevo en unos minutos.');
        }

        if (empty($response['id'])) {
            return OutfitAiResult::failed($this->errorMessage($response['error'] ?? $response['message'] ?? null));
        }

        $reference = Str::random(40);
        Cache::put(self::CACHE_PREFIX.$reference, [
            'prediction_id' => $response['id'],
            'remaining' => $garments,
        ], now()->addMinutes(30));

        return OutfitAiResult::processing($reference);
    }

    private function storeResult(string $output): OutfitAiResult
    {
        $binary = str_starts_with($output, 'http')
            ? Http::timeout(30)->get($output)->throw()->body()
            : base64_decode((string) preg_replace('#^data:[^;]+;base64,#', '', $output), true);

        if (! $binary || @imagecreatefromstring($binary) === false) {
            return OutfitAiResult::failed('El probador no devolvió una imagen válida.');
        }

        $path = OutfitPhotoStore::DIRECTORY.'/results/'.Str::random(40).'.jpg';
        Storage::disk('local')->put($path, $binary);

        return OutfitAiResult::completed($path);
    }

    /**
     * Prenda superior primero y luego la inferior, con la foto del color elegido.
     *
     * @return list<array{name: string, category: string, image: ?string}>
     */
    private function garmentsFor(array $variantIds): array
    {
        $variants = Producto::query()->whereIn('id', $variantIds)->get();
        $categoryNames = Categoria::query()->whereIn('id', $variants->pluck('categoria_id')->filter())->pluck('name', 'id');

        return $variants
            ->map(function (Producto $variant) use ($categoryNames): ?array {
                $slot = StorefrontCategories::slotFor(StorefrontCategories::slugFor($variant->name, $categoryNames->get($variant->categoria_id)));

                if ($slot === 'extra') {
                    return null;
                }

                return [
                    'name' => Str::title(mb_strtolower($variant->name)),
                    'category' => $slot === 'top' ? 'tops' : 'bottoms',
                    'image' => $this->garmentImage($variant),
                ];
            })
            ->filter()
            ->sortBy(fn (array $garment) => $garment['category'] === 'tops' ? 0 : 1)
            ->values()
            ->all();
    }

    private function garmentImage(Producto $variant): ?string
    {
        $url = ProductoColorImage::query()
            ->where('product_name', $variant->name)
            ->where('color', $variant->color)
            ->first()
            ?->imageUrls()[0]
            ?? $variant->imageUrl()
            ?? Producto::query()->where('name', $variant->name)->whereNotNull('imagen')->first()?->imageUrl();

        if ($url === null) {
            return null;
        }

        if (str_starts_with($url, '/storage/')) {
            $path = substr($url, strlen('/storage/'));
            $disk = Storage::disk('public');

            return $disk->exists($path) ? $this->dataUri($disk->get($path), $disk->mimeType($path) ?: 'image/jpeg') : null;
        }

        return filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
    }

    private function asDataUri(string $output): string
    {
        return str_starts_with($output, 'data:') || str_starts_with($output, 'http')
            ? $output
            : 'data:image/jpeg;base64,'.$output;
    }

    private function dataUri(string $binary, string $mime): string
    {
        return 'data:'.$mime.';base64,'.base64_encode($binary);
    }

    private function errorMessage(mixed $error): string
    {
        $name = is_array($error) ? ($error['name'] ?? '') : (string) $error;

        return match (true) {
            str_contains($name, 'PoseError') => 'No detectamos bien tu cuerpo en la foto. Prueba con una foto de frente, de cuerpo más completo y bien iluminada.',
            str_contains($name, 'ContentModeration') => 'La imagen no pasó el filtro de contenido del probador.',
            default => 'No se pudo generar la prueba. Inténtalo de nuevo con otra foto o prenda.',
        };
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.fashn.base_url'), '/'))
            ->withToken((string) config('services.fashn.api_key'))
            ->acceptJson()
            ->asJson()
            ->connectTimeout(10)
            ->timeout((int) config('services.fashn.timeout', 20));
    }
}

<?php

namespace App\Services\OutfitAi;

/**
 * Resultado de una operación del probador con IA.
 *
 * - unavailable: no hay proveedor configurado o rechazó la operación por configuración.
 * - processing: la operación sigue en curso; consultar status() con $reference.
 * - completed: $imagePath apunta a la imagen resultante en el disco privado "local".
 * - failed: la operación terminó con error; $message describe el motivo para el cliente.
 */
final class OutfitAiResult
{
    public const UNAVAILABLE = 'unavailable';

    public const PROCESSING = 'processing';

    public const COMPLETED = 'completed';

    public const FAILED = 'failed';

    private function __construct(
        public readonly string $status,
        public readonly ?string $reference = null,
        public readonly ?string $imagePath = null,
        public readonly ?string $message = null,
    ) {}

    public static function unavailable(string $message = 'La generación con IA todavía no está configurada en la tienda.'): self
    {
        return new self(self::UNAVAILABLE, message: $message);
    }

    public static function processing(string $reference): self
    {
        return new self(self::PROCESSING, reference: $reference);
    }

    public static function completed(string $imagePath, ?string $reference = null): self
    {
        return new self(self::COMPLETED, reference: $reference, imagePath: $imagePath);
    }

    public static function failed(string $message): self
    {
        return new self(self::FAILED, message: $message);
    }
}

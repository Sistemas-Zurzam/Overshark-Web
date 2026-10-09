<?php

namespace App\Services\OutfitAi;

/**
 * Implementación por defecto mientras no exista un proveedor contratado: nunca envía datos fuera
 * del servidor y responde siempre "no disponible".
 */
class UnconfiguredOutfitAiGenerator implements OutfitAiGenerator
{
    public function isAvailable(): bool
    {
        return false;
    }

    public function providerName(): ?string
    {
        return null;
    }

    public function generateModel(string $photoPath): OutfitAiResult
    {
        return OutfitAiResult::unavailable();
    }

    public function tryOn(string $modelPath, array $variantIds): OutfitAiResult
    {
        return OutfitAiResult::unavailable();
    }

    public function status(string $reference): OutfitAiResult
    {
        return OutfitAiResult::unavailable();
    }

    public function supportsMultiView(): bool
    {
        return false;
    }
}

<?php

namespace App\Services\OutfitAi;

/**
 * Punto de integración del proveedor de IA del probador.
 *
 * Una implementación real debe:
 * - Llamar al proveedor solo desde Laravel, con credenciales leídas de config/.env.
 * - Devolver processing() cuando la operación sea larga y resolverla luego desde un job en cola,
 *   persistiendo el estado (la sesión no es accesible desde un worker).
 * - Guardar las imágenes resultantes en el disco privado "local", bajo "outfit/".
 * - No registrar en logs fotos ni respuestas del proveedor que contengan imágenes.
 */
interface OutfitAiGenerator
{
    public function isAvailable(): bool;

    /**
     * Nombre visible del proveedor al que se enviará la foto (se muestra en la aceptación).
     */
    public function providerName(): ?string;

    /**
     * Genera el modelo de la persona a partir de su foto (ruta relativa al disco "local").
     */
    public function generateModel(string $photoPath): OutfitAiResult;

    /**
     * Aplica sobre un modelo generado las variantes de producto elegidas.
     *
     * @param  list<int>  $variantIds
     */
    public function tryOn(string $modelPath, array $variantIds): OutfitAiResult;

    /**
     * Consulta una operación iniciada con processing().
     */
    public function status(string $reference): OutfitAiResult;

    /**
     * Si el proveedor entrega varias vistas o un modelo 3D (habilita "Rotar modelo 360°").
     */
    public function supportsMultiView(): bool;
}

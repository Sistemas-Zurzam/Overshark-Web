<?php

namespace App\Providers;

use App\Services\OutfitAi\FashnOutfitAiGenerator;
use App\Services\OutfitAi\OutfitAiGenerator;
use App\Services\OutfitAi\UnconfiguredOutfitAiGenerator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Probador con IA: FASHN si hay FASHN_API_KEY; si no, el probador funciona sin generación.
        $this->app->bind(OutfitAiGenerator::class, fn ($app) => filled(config('services.fashn.api_key'))
            ? $app->make(FashnOutfitAiGenerator::class)
            : $app->make(UnconfiguredOutfitAiGenerator::class));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

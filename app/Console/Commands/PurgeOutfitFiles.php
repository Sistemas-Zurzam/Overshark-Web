<?php

namespace App\Console\Commands;

use App\Services\OutfitPhotoStore;
use Illuminate\Console\Command;

class PurgeOutfitFiles extends Command
{
    protected $signature = 'outfit:purge {--hours='.OutfitPhotoStore::RETENTION_HOURS.' : Antigüedad mínima en horas}';

    protected $description = 'Elimina las fotos y resultados temporales del probador con IA';

    public function handle(OutfitPhotoStore $photos): int
    {
        $deleted = $photos->purgeOlderThan(max(1, (int) $this->option('hours')));

        $this->info("Probador: {$deleted} archivos temporales eliminados.");

        return self::SUCCESS;
    }
}

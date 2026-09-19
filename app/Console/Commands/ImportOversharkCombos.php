<?php

namespace App\Console\Commands;

use App\Services\ComboCatalogImportService;
use Illuminate\Console\Command;

class ImportOversharkCombos extends Command
{
    protected $signature = 'combos:import-overshark';

    protected $description = 'Carga o actualiza el catálogo de combos de Overshark';

    public function handle(ComboCatalogImportService $importer): int
    {
        $result = $importer->import();

        $this->info("Combos creados: {$result['created']}");
        $this->info("Combos actualizados: {$result['updated']}");

        if ($result['missing'] !== []) {
            $this->warn('Productos pendientes de vincular: '.implode(', ', $result['missing']));
        }

        return self::SUCCESS;
    }
}

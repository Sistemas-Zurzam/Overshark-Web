<?php

namespace App\Console\Commands;

use App\Services\ZazuInventorySyncService;
use Illuminate\Console\Command;
use Throwable;

class SyncZazuProducts extends Command
{
    protected $signature = 'zazu:sync-products';

    protected $description = 'Sincroniza productos activos e inventario disponible desde Zazu 2';

    public function handle(ZazuInventorySyncService $sync): int
    {
        try {
            $result = $sync->sync();
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Zazu sincronizado: %d recibidos, %d actualizados, %d antiguos con stock cero.',
            $result['received'],
            $result['synced'],
            $result['stale_zeroed'],
        ));

        return self::SUCCESS;
    }
}

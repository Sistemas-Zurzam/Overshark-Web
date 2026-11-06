<?php

namespace App\Console\Commands;

use App\Services\ZazuAgencySyncService;
use Illuminate\Console\Command;
use Throwable;

class SyncZazuAgencies extends Command
{
    protected $signature = 'zazu:sync-agencies';

    protected $description = 'Sincroniza las sedes Shalom activas desde Zazu';

    public function handle(ZazuAgencySyncService $sync): int
    {
        try {
            $result = $sync->sync();
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Sedes Shalom sincronizadas desde Zazu: {$result['synced']}.");

        return self::SUCCESS;
    }
}

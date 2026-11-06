<?php

namespace App\Console\Commands;

use App\Support\PromoCombos;
use Illuminate\Console\Command;

class CreatePromoCombos extends Command
{
    protected $signature = 'combos:promociones';

    protected $description = 'Crea los combos con flyer de "Elige tu combo" (Overshark, Bravos y Girls); los que ya existen no se tocan';

    public function handle(): int
    {
        $result = PromoCombos::create();

        $this->info('Combos creados: '.count($result['created']));
        $this->line('Ya existían (no se tocaron): '.count($result['existing']));

        if ($result['missing'] !== []) {
            $this->warn('Productos que no están en el catálogo: '.implode(', ', $result['missing']).'. Complétalos en Admin → Combos.');
        }

        return self::SUCCESS;
    }
}

<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/** Días hábiles (lunes a viernes). No descuenta feriados: suficiente para el plazo legal del libro de reclamaciones. */
class BusinessDays
{
    public static function addDays(Carbon $date, int $days): Carbon
    {
        $result = $date->copy();
        while ($days > 0) {
            $result->addDay();
            if (! $result->isWeekend()) {
                $days--;
            }
        }

        return $result;
    }

    /** Días hábiles completos entre dos fechas; negativo si $to ya pasó respecto a $from. */
    public static function countBetween(Carbon $from, Carbon $to): int
    {
        if ($to->lessThan($from)) {
            return -self::countBetween($to, $from);
        }

        $count = 0;
        $cursor = $from->copy()->startOfDay();
        $end = $to->copy()->startOfDay();
        while ($cursor->lessThan($end)) {
            $cursor->addDay();
            if (! $cursor->isWeekend()) {
                $count++;
            }
        }

        return $count;
    }
}

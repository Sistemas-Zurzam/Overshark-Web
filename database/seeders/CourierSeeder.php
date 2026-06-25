<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class CourierSeeder extends Seeder
{
    public function run(): void
    {
        $path = env('COURIER_SQL_PATH', 'C:/Users/Drako/Downloads/courier.sql');

        if (! is_file($path)) {
            throw new RuntimeException("No se encontro el archivo courier.sql en: {$path}");
        }

        [$columns, $rows] = $this->readCourierDump($path);

        Schema::disableForeignKeyConstraints();
        DB::table('courier')->truncate();
        Schema::enableForeignKeyConstraints();

        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table('courier')->insert(array_map(
                fn (array $values): array => array_combine($columns, $values),
                $chunk
            ));
        }

        $this->command?->info('Courier importado correctamente: '.count($rows).' registros.');
    }

    private function parseMysqlRows(string $values): array
    {
        $rows = [];
        $current = '';
        $depth = 0;
        $inString = false;
        $length = strlen($values);

        for ($i = 0; $i < $length; $i++) {
            $char = $values[$i];
            $next = $i + 1 < $length ? $values[$i + 1] : '';

            if ($char === "'" && ! $this->isEscaped($values, $i)) {
                $inString = ! $inString;
            }

            if (! $inString) {
                if ($char === '(') {
                    $depth++;

                    if ($depth === 1) {
                        continue;
                    }
                }

                if ($char === ')') {
                    $depth--;

                    if ($depth === 0) {
                        $rows[] = $this->parseMysqlFields($current);
                        $current = '';
                        continue;
                    }
                }

                if ($depth === 0 || ($char === ',' && $next === "\n")) {
                    continue;
                }
            }

            $current .= $char;
        }

        return $rows;
    }

    private function readCourierDump(string $path): array
    {
        $columns = [];
        $rows = [];
        $collecting = false;
        $buffer = '';

        foreach (file($path) as $line) {
            if (preg_match('/^INSERT INTO `courier` \((.*?)\) VALUES\s*$/', rtrim($line), $matches)) {
                $columns = array_map(
                    fn (string $column): string => trim($column, " `\t\n\r\0\x0B"),
                    explode(',', $matches[1])
                );
                $collecting = true;
                $buffer = '';
                continue;
            }

            if (! $collecting) {
                continue;
            }

            $trimmedLine = rtrim($line);
            $isLastLine = str_ends_with($trimmedLine, ';');
            $buffer .= ($isLastLine ? rtrim($trimmedLine, ';') : $trimmedLine)."\n";

            if ($isLastLine) {
                $rows = array_merge($rows, $this->parseMysqlRows(trim($buffer)));
                $collecting = false;
                $buffer = '';
            }
        }

        if ($columns === [] || $rows === []) {
            throw new RuntimeException('No se encontraron registros para courier.');
        }

        return [$columns, $rows];
    }

    private function parseMysqlFields(string $row): array
    {
        return array_map(function (?string $value): ?string {
            if ($value === null || strtoupper($value) === 'NULL') {
                return null;
            }

            return str_replace(["\\'", "''"], ["'", "'"], $value);
        }, str_getcsv($row, ',', "'", '\\'));
    }

    private function isEscaped(string $value, int $position): bool
    {
        $slashes = 0;

        for ($i = $position - 1; $i >= 0 && $value[$i] === '\\'; $i--) {
            $slashes++;
        }

        return $slashes % 2 === 1;
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class MeasurementRepository
{
    public function __construct(private readonly ?PDO $pdo = null) {}

    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        if ($this->pdo !== null) {
            $rows = $this->pdo->query('SELECT id, measured_at, tsf, razon_oa, conductividad, estado, archivo, equipo FROM mediciones ORDER BY measured_at')->fetchAll();
            return array_map([$this, 'normalizeDatabaseRow'], $rows);
        }

        return $this->fromFile(dirname(__DIR__, 2) . '/Analisis.txt');
    }

    /** @return list<array<string, mixed>> */
    private function fromFile(string $path): array
    {
        $records = [];
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $parts = str_getcsv($line, ';');
            if (count($parts) < 7 || !preg_match('/^(\d{2})-(\d{2})-(\d{4})-(\d{2}:\d{2}:\d{2})$/', $parts[0], $match)) {
                continue;
            }
            $records[] = [
                'id' => count($records) + 1,
                'iso' => "{$match[3]}-{$match[2]}-{$match[1]}T{$match[4]}",
                'fecha' => "{$match[1]}-{$match[2]}-{$match[3]}",
                'hora' => $match[4],
                'tiempo' => (float) str_replace(',', '.', $parts[2]),
                'razon' => (float) str_replace(',', '.', $parts[4]),
                'conductividad' => (float) str_replace(',', '.', $parts[6]),
                'archivo' => basename($path),
                'equipo' => 'Analizador local 01',
            ];
        }
        return $records;
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private function normalizeDatabaseRow(array $row): array
    {
        $date = new \DateTimeImmutable((string) $row['measured_at']);
        return [
            'id' => (int) $row['id'], 'iso' => $date->format('Y-m-d\TH:i:s'),
            'fecha' => $date->format('d-m-Y'), 'hora' => $date->format('H:i:s'),
            'tiempo' => (float) $row['tsf'], 'razon' => (float) $row['razon_oa'],
            'conductividad' => (float) $row['conductividad'], 'archivo' => $row['archivo'],
            'equipo' => $row['equipo'],
        ];
    }
}

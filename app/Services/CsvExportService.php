<?php

namespace App\Services;

class CsvExportService
{
    /**
     * @param  string[]  $headers
     * @param  iterable<int, array<int, mixed>>  $rows
     */
    public function toCsv(array $headers, iterable $rows): string
    {
        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, $headers);

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }
}

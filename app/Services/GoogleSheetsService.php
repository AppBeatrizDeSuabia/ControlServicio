<?php

namespace App\Services;

use Google\Client;
use Google\Service\Sheets;

class GoogleSheetsService
{
    protected $client;
    protected $service;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setAuthConfig(storage_path('app/institutoimport-a87b3220723c.json'));
        $this->client->addScope(Sheets::SPREADSHEETS);
        $this->service = new Sheets($this->client);
    }

    public function getSheetData($spreadsheetId, $range) {
        $response = $this->service->spreadsheets_values->get($spreadsheetId, $range);
        $values = $response->getValues();
        
        if (empty($values)) return [];

        // 1. Limpiamos la cabecera y contamos cuántas columnas DEBE haber
        $header = array_map('trim', $values[0]);
        $columnCount = count($header);
        
        $data = [];
        for ($i = 1; $i < count($values); $i++) {
            $row = $values[$i];

            $fullRow = array_slice(array_pad($row, $columnCount, ''), 0, $columnCount);

            $data[] = array_combine($header, $fullRow);
        }
        return $data;
    }

    public function writeSheetData($spreadsheetId, $range, $rows)
    {
        $values = [];

        // 1️⃣ Cabeceras
        $values[] = ['Alumno', 'Profesor', 'Baño', 'Salida', 'Regreso'];

        // 2️⃣ Filas de datos
        foreach ($rows as $row) {
            $values[] = [
                $row['alumn'],
                $row['teacher'],
                $row['bathroom'] ?? 'Sin baño asignado',
                $row['created_at'] instanceof \DateTime
                    ? $row['created_at']->format('Y-m-d H:i:s')
                    : $row['created_at'],
                $row['returned_at'] instanceof \DateTime
                    ? $row['returned_at']->format('Y-m-d H:i:s')
                    : ($row['returned_at'] ?? ''),
            ];
        }

        $body = new \Google\Service\Sheets\ValueRange([
            'values' => $values
        ]);

        $params = ['valueInputOption' => 'RAW'];

        // 3️⃣ Limpiar la hoja antes de escribir
        $this->service->spreadsheets_values->clear(
            $spreadsheetId,
            $range,
            new \Google\Service\Sheets\ClearValuesRequest()
        );

        // 4️⃣ Escribir datos + cabeceras
        $this->service->spreadsheets_values->update(
            $spreadsheetId,
            $range,
            $body,
            $params
        );
    }

    // public function writeSheetData($spreadsheetId, $range, $rows)
    // {
    //     $values = [];

    //     // 1️⃣ Filas de datos (sin cabecera, porque append deja la cabecera intacta)
    //     foreach ($rows as $row) {
    //         $values[] = [
    //             $row['alumn'],
    //             $row['teacher'],
    //             $row['created_at'] instanceof \DateTime ? $row['created_at']->format('Y-m-d H:i:s') : $row['created_at'],
    //             $row['returned_at'] instanceof \DateTime ? $row['returned_at']->format('Y-m-d H:i:s') : ($row['returned_at'] ?? '')
    //         ];
    //     }

    //     $body = new \Google\Service\Sheets\ValueRange([
    //         'values' => $values
    //     ]);

    //     $params = [
    //         'valueInputOption' => 'RAW',
    //         'insertDataOption' => 'INSERT_ROWS' // Añade nuevas filas en lugar de sobrescribir
    //     ];

    //     // 2️⃣ Usamos append en lugar de clear + update
    //     $this->service->spreadsheets_values->append(
    //         $spreadsheetId,
    //         $range,   // por ejemplo 'bathroom_permissions!A:D'
    //         $body,
    //         $params
    //     );
    // }
}

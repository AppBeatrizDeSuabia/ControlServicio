<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Alumn;
use App\Models\Teacher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Services\GoogleSheetsService;

class importController extends Controller
{
    //
    public function import(Request $request, $type) {

        $sheetService = new GoogleSheetsService();

        // ⚠️ Pega aquí el ID real de tu Google Sheet
        $spreadsheetId = config('services.google_sheets.spreadsheet_id');

        // Leer datos desde Google Sheets según el tipo
        if ($type === 'teachers') {
            $rows = $sheetService->getSheetData($spreadsheetId, 'teachers!A:D');
        } elseif ($type === 'alumns') {
            $rows = $sheetService->getSheetData($spreadsheetId, 'alumns!A:B');
        } else {
            return back()->with('error', 'Tipo de importación no válido.');
        }

        try {
            DB::transaction(function () use ($rows, $type) {

                if ($type === 'teachers') {

                    DB::table('teachers')->truncate();

                    foreach ($rows as $row) {

                        $email = trim($row['email']);

                        $teacher = Teacher::create([
                            'full_name' => trim($row['full_name']),
                            'email' => $email,
                            'is_admin' => !empty($row['is_admin'] ?? false),

                            // 🔐 contraseña solo si es admin
                            'password' => ($row['is_admin'] ?? false)
                                ? Teacher::DEFAULT_ADMIN_PASSWORD
                                : null,

                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                } elseif ($type === 'alumns') {

                    DB::table('alumns')->truncate();
                    DB::table('courses')->truncate();

                    $uniqueCourses = [];

                    foreach ($rows as $row) {
                        $courseName = trim($row['curso'] ?? '');
                        if ($courseName && !in_array($courseName, $uniqueCourses)) {
                            $uniqueCourses[] = $courseName;
                        }
                    }

                    usort($uniqueCourses, function($a, $b) {
                        $courseOrder = [
                            'ESO'   => 1,
                            'BACH'  => 2,
                            'CFGB'  => 3,
                            'CMPEL' => 4,
                            'CMADM' => 5,
                            'CMEST' => 6,
                            'CSEIB' => 7,
                            'CSPEL' => 8,
                            'CSCAR' => 9,
                            'CSAD'  => 10,
                            'CSAIP' => 11,
                            'CSAYF' => 12,
                        ];

                        $getPriority = function ($name) use ($courseOrder) {
                            foreach ($courseOrder as $prefix => $priority) {
                                if (str_contains(strtoupper($name), $prefix)) {
                                    return $priority;
                                }
                            }

                            return 99;
                        };

                        $pA = $getPriority($a);
                        $pB = $getPriority($b);

                        if ($pA !== $pB) {
                            return $pA <=> $pB;
                        }

                        return $a <=> $b;
                    });

                    $courseMap = [];

                    foreach ($uniqueCourses as $name) {
                        $courseMap[$name] = DB::table('courses')->insertGetId([
                            'name' => $name,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    foreach ($rows as $row) {

                        logger('Importando alumno', $row);

                        $courseName = trim($row['curso'] ?? '');

                        if (!$courseName || !isset($courseMap[$courseName])) {
                            continue;
                        }

                        Alumn::create([
                            'full_name' => trim($row['full_name']),
                            'course_id' => $courseMap[$courseName],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

            });

        } catch (\Exception $e) {

            logger('Error al importar desde Google Sheets', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Error al importar desde Google Sheets: '.$e->getMessage());
        }

        return back()->with('success', 'Importación desde Google Sheets completada correctamente.');
    }

}

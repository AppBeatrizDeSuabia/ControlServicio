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
        $spreadsheetId = env('GOOGLE_SHEETS_ID');

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
                        $priorities = ['ESO' => 1, 'BACH' => 2, 'IF' => 3];
                        $getPriority = function($name) use ($priorities) {
                            foreach ($priorities as $key => $p) {
                                if (str_contains(strtoupper($name), $key)) return $p;
                            }
                            return 99;
                        };
                        $pA = $getPriority($a);
                        $pB = $getPriority($b);
                        if ($pA != $pB) return $pA <=> $pB;
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

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BathroomPermission;
use App\Services\GoogleSheetsService;
use App\Models\Setting;

class ExportAndCleanPermissions extends Command
{
    protected $signature = 'permissions:export-clean';
    protected $description = 'Exporta permisos a Google Sheets y limpia la tabla';

    public function handle()
    {
        $sheetService = new GoogleSheetsService();

        $spreadsheetId = env('GOOGLE_SHEETS_ID');

        // 1️⃣ Actualizar permisos vencidos
        BathroomPermission::whereNull('returned_at')
            ->where('created_at', '<=', now()->subMinutes(Setting::get('permission_duration_minutes', 15)))
            ->get()
            ->each(function ($permission) {
                $permission->update([
                    'returned_at' => $permission->created_at->copy()->addMinutes(Setting::get('permission_duration_minutes', 15))
                ]);
            });

        // 2️⃣ Obtener todos los permisos
        $permissions = BathroomPermission::with('teacher', 'alumn')
            ->orderBy('created_at')
            ->get();

        $rows = [];

        foreach ($permissions as $permission) {
            $rows[] = [
                'alumn' => $permission->alumn?->full_name ?? 'Sin alumno',
                'teacher' => $permission->teacher?->full_name ?? 'Sin profesor',
                'created_at' => $permission->created_at,
                'returned_at' => $permission->returned_at
            ];
        }

        // 3️⃣ Exportar a Google Sheets
        $sheetService->writeSheetData(
            $spreadsheetId,
            'bathroom_permissions!A:D',
            $rows
        );

        // 4️⃣ BORRAR TODO EL HISTORIAL
        BathroomPermission::truncate();

        $this->info('Permisos exportados y base de datos limpiada correctamente.');
    }
}
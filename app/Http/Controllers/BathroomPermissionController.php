<?php

namespace App\Http\Controllers;

use App\Models\Alumn;
use Illuminate\Http\Request;
use App\Models\BathroomPermission;
use App\Models\Course;
use App\Services\GoogleSheetsService;
use App\Models\Setting;


class BathroomPermissionController extends Controller
{
    //
    public function index(Request $request) {

        $bathrooms = [
            'chicos_1' => 'Baño chicos edificio 1',
            'chicas_1' => 'Baño chicas edificio 1',
            'chicos_2' => 'Baño chicos edificio 2',
            'chicas_2' => 'Baño chicas edificio 2',
        ];

        $permissionDuration = (int) Setting::get('permission_duration_minutes', 15);

        //Filtro para permisos activos y comprueba si llevan más de 15 minutos, si lo lleva se actualiza el returned_at de null a la fecha actual.
        BathroomPermission::whereNull('returned_at')
        ->where('created_at', '<=', now()->copy()->subMinutes($permissionDuration))
        ->get()
        ->each(function ($permission) use ($permissionDuration) {
            $permission->update([
                'returned_at' => $permission->created_at
                    ->copy()
                    ->addMinutes($permissionDuration),
            ]);
        });


        //Guarda en una variable todos los permisos que tengan null en returned_at y el profesor que haya creado el permiso.
        $activePermissions = BathroomPermission::whereNull('returned_at')->with('teacher', 'alumn')->get();

        //Cuenta todos los permisos que hay activos actualmente.
        // $currentCount = $activePermissions->count();

        // $maxPermissions = Setting::get('max_permissions', 5);

        $currentCountByBathroom = $activePermissions
            ->groupBy('bathroom')
            ->map(fn ($permissions) => $permissions->count());

        $maxPermissionsByBathroom = [];

        foreach ($bathrooms as $key => $label) {
            $maxPermissionsByBathroom[$key] = (int) Setting::get("max_permissions_{$key}", 5);
        }

        $maxDailyPerAlumn   = Setting::get('max_daily_per_alumn', 3);

        //Guardo en una variable todos los cursos.
        $courses = Course::all();

        $alumns = collect();

        $courseId = $request->course_id ?? null;
        
        if ($request->filled('course_id')) {
            $alumns = Alumn::where('course_id', $courseId)->get();
        }

        $salidasHoy = BathroomPermission::whereDate('created_at', now())->selectRaw('alumn_id, COUNT(*) as total')->groupBy('alumn_id')->pluck('total', 'alumn_id');

        // En tu BathroomPermissionController@index
        return response()
            ->view('dashboard', compact(
                'activePermissions',
                'courses',
                'alumns',
                'courseId',
                'salidasHoy',
                'maxDailyPerAlumn',
                'permissionDuration',
                'bathrooms',
                'currentCountByBathroom',
                'maxPermissionsByBathroom'
            ))
            ->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sat, 01 Jan 1990 00:00:00 GMT');

        //Devuelve una vista enviándole la información de la cantidad de permisos activos actualmente y la información de cada permiso con la id del profesor que ha creado dicho permiso.
    }

    public function givePermission(Request $request) {

        $bathrooms = [
            'chicos_1',
            'chicas_1',
            'chicos_2',
            'chicas_2',
        ];

        $request->validate([
            'alumn_id' => 'required|exists:alumns,id',
            'bathroom' => ['required', \Illuminate\Validation\Rule::in($bathrooms)],
        ]);

        $profesor = session('profesor'); // trae el profesor de la sesión

        $max = (int) Setting::get("max_permissions_{$request->bathroom}", 5);

        $current = BathroomPermission::where('bathroom', $request->bathroom)
            ->whereNull('returned_at')
            ->count();

        if ($current >= $max) {
            return back()->withInput()->with(
                'error',
                'Ese baño ya ha alcanzado su límite de permisos activos.'
            );
        }

        //Si pasa del if porque hay hueco para otro permiso, crea un permiso con la id del profesor logueado.
        BathroomPermission::create([
            'teacher_id' => $profesor->id,
            'alumn_id' => $request->alumn_id,
            'bathroom' => $request->bathroom,
        ]);

        //Vuelve al index con la información de los permisos y un mensaje de que el permiso se ha creado correctamente.
        return redirect()->route('dashboard')->with('success', 'Permiso concedido');
    }

    public function markReturned($id) {
        $profesor = session('profesor'); // trae el profesor de la sesión
        
        //Guarda en una variable un permiso con la id solicitada.
        $permission = BathroomPermission::findOrFail($id);

        //Compara si el profesor logueado que esta intentando borrar un permiso es el mismo que lo ha creado, si no es el mismo salta un error de permisos y corta la ejecución.
        if ($permission->teacher_id !== $profesor->id) {
            abort(403);
        }

        // ⛔ Si ya pasaron 15 minutos, forzar hora correcta
        $permissionDuration = (int) Setting::get('permission_duration_minutes', 15);

        $expiresAt = $permission->created_at
            ->copy()
            ->addMinutes($permissionDuration);

        if ($expiresAt->isPast()) {
            $permission->update([
                'returned_at' => $expiresAt,
            ]);
        } else {
            $permission->update([
                'returned_at' => now(),
            ]);
        }

        //Vuelve al index
        return back();
    }

    public function history(Request $request)
    {
        $bathrooms = [
            'chicos_1' => 'Baño chicos edificio 1',
            'chicas_1' => 'Baño chicas edificio 1',
            'chicos_2' => 'Baño chicos edificio 2',
            'chicas_2' => 'Baño chicas edificio 2',
        ];

        $request->validate([
            'course_id' => 'nullable|exists:courses,id',
            'alumn_id' => 'nullable|exists:alumns,id',
            'bathroom' => [
                'nullable',
                \Illuminate\Validation\Rule::in(array_keys($bathrooms)),
            ],
        ]);

        $query = BathroomPermission::with('teacher', 'alumn')
            ->orderBy('created_at', 'desc');

        // Si se eligió curso, conservar permisos de alumnos de ese curso.
        if ($request->filled('course_id')) {
            $courseId = $request->input('course_id');

            $query->whereHas('alumn', function ($alumnQuery) use ($courseId) {
                $alumnQuery->where('course_id', $courseId);
            });
        }

        // Si se eligió alumno, limitar además a ese alumno.
        if ($request->filled('alumn_id')) {
            $query->where('alumn_id', $request->input('alumn_id'));
        }

        // Si se eligió baño, combinarlo con los filtros anteriores.
        if ($request->filled('bathroom')) {
            $query->where('bathroom', $request->input('bathroom'));
        }

        $permissions = $query->get();

        $courses = Course::orderBy('name')->get();

        // El desplegable de alumnos contiene los del curso elegido.
        $alumns = $request->filled('course_id')
            ? Alumn::where('course_id', $request->input('course_id'))
                ->orderBy('full_name')
                ->get()
            : collect();

        $courseId = $request->input('course_id');
        $alumnId = $request->input('alumn_id');
        $selectedBathroom = $request->input('bathroom');

        return view('bathroom_permissions.history', compact(
            'permissions',
            'bathrooms',
            'courses',
            'alumns',
            'courseId',
            'alumnId',
            'selectedBathroom'
        ));
    }

    public function exportPermissions()
    {
        $bathroomNames = [
            'chicos_1' => 'Baño chicos edificio 1',
            'chicas_1' => 'Baño chicas edificio 1',
            'chicos_2' => 'Baño chicos edificio 2',
            'chicas_2' => 'Baño chicas edificio 2',
        ];

        $sheetService = new GoogleSheetsService();

        $spreadsheetId = config('services.google_sheets.spreadsheet_id');

        $permissionDuration = (int) Setting::get('permission_duration_minutes', 15);

        // 1️⃣ Actualizar permisos vencidos antes de exportar
        BathroomPermission::whereNull('returned_at')->where('created_at', '<=', now()->copy()->subMinutes($permissionDuration))->get()->each(function ($permission) use ($permissionDuration) {
            $permission->update([
                'returned_at' => $permission->created_at
                    ->copy()
                    ->addMinutes($permissionDuration),
            ]);
        });

        // 2️⃣ Obtener todos los permisos con relaciones
        $permissions = BathroomPermission::with('teacher','alumn')->orderBy('created_at')->get();

        $rows = [];

        foreach ($permissions as $permission) {
            $rows[] = [
                'alumn' => $permission->alumn?->full_name ?? 'Sin alumno',
                'teacher' => $permission->teacher?->full_name ?? 'Sin profesor',
                'bathroom' => $bathroomNames[$permission->bathroom] ?? 'Sin baño asignado',
                'created_at' => $permission->created_at,
                'returned_at' => $permission->returned_at
            ];
        }

        try {
            $sheetService->writeSheetData(
                $spreadsheetId,
                'bathroom_permissions!A:E',
                $rows
            );
        } catch (\Exception $e) {
            logger('Error exportando permisos', ['message' => $e->getMessage()]);
            return back()->with('error','Error exportando datos: '.$e->getMessage());
        }

        return back()->with('success','Permisos exportados correctamente a Google Sheets.');
    }
}

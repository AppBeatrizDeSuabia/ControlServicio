<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;

class SettingsController extends Controller
{
    public function update(Request $request)
    {
        $bathrooms = [
            'chicos_1',
            'chicas_1',
            'chicos_2',
            'chicas_2',
        ];

        $rules = [
            'max_daily_per_alumn' => 'required|integer|min:1',
            'permission_duration_minutes' => 'required|integer|min:1',
        ];

        foreach ($bathrooms as $bathroom) {
            $rules["max_permissions_{$bathroom}"] = 'required|integer|min:1';
        }

        $request->validate($rules);

        foreach ($bathrooms as $bathroom) {
            Setting::updateOrCreate(
                ['key' => "max_permissions_{$bathroom}"],
                ['value' => $request->input("max_permissions_{$bathroom}")]
            );
        }

        Setting::updateOrCreate(
            ['key' => 'max_daily_per_alumn'],
            ['value' => $request->max_daily_per_alumn]
        );

        Setting::updateOrCreate(
            ['key' => 'permission_duration_minutes'],
            ['value' => $request->permission_duration_minutes]
        );

        return back()->with('success', 'Configuración actualizada correctamente');
    }
}
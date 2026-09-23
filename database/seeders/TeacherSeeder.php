<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Teacher;
use Illuminate\Support\Facades\Hash;

class TeacherSeeder extends Seeder 
{ 
    /** * Run the database seeds. */ 
    public function run(): void 
    { 
        Teacher::create([ 
            'full_name' => 'Francisco Javier Moreno Muñoz', 
            'email' => 'javiermm.04@gmail.com', 
            'is_admin' => true, 
            'password' => Teacher::DEFAULT_ADMIN_PASSWORD, 
            'created_at' => now(), 
            'updated_at' => now(), 
        ]); 
    } 
}
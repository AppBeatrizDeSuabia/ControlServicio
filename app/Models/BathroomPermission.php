<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BathroomPermission extends Model
{
    //

    use HasFactory;

    //Permite cambios en los campos añadidos.
    protected $fillable = [
        'teacher_id',
        'alumn_id',
        'bathroom',
        'returned_at'
    ];

    //Establece la relación entre las tablas.
    public function teacher() {
        return $this->belongsTo(Teacher::class);
    }

    public function alumn() {
        return $this->belongsTo(Alumn::class);
    }
}

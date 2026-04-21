<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grupo extends Model
{
    protected $table = 'grupos';
    public $timestamps = false;
    public $incrementing = false;
    // ← sin PK simple

    protected $fillable = [
        'anio',
        'periodo',
        'plan',
        'materia',
        'grupo',
        'docente',
        'primario',
        'cuota',
        'tipo',
        'quota_plan',
        'resolucion',
        'designacion',
        'tiempo'
    ];
}

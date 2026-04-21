<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Materia extends Model
{
    protected $table = 'materias';
    public $timestamps = false;
    public $incrementing = false; // ← sin autoincrement
    protected $primaryKey = null; // ← sin PK simple

    protected $fillable = [
        'anio',
        'plan',
        'codigo',
        'sigla',
        'nombre',
        'nivel',
        'periodo',
        'obligatorio',
        'tipo',
        'activo',
        'departamento'
    ];
}
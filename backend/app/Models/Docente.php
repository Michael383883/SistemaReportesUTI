<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Docente extends Model
{
    protected $table = 'docentes';

    // Clave primaria personalizada
    protected $primaryKey = 'codigo';

    // No es autoincremental
    public $incrementing = false;

    // Tipo de clave (numeric en SQL Server → int/string según caso)
    protected $keyType = 'string';

    protected $fillable = [
        'codigo',
        'ci',
        'nombres',
        'apellidos',
        'fecha_nac',
        'sexo',
        'titulo',
        'fecha_nombramiento'
    ];
}
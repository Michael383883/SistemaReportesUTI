<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocenteTelefono extends Model
{
    protected $table = 'docentes_telefono';

    protected $primaryKey = 'docente'; // o 'id' según tu estructura

    public $incrementing = false; // Si 'docente' no es autoincrementable

    protected $fillable = [
        'docente',
        'nombre_docente',
        'ci',
        'fecha_nac',
        'email',
        'direccion',
        'email',
        'unidad',
        'fijo_1',
        'email_institucional',
        'fijo_2',
        'celular_1',
        'celular_2',
        'tipo_doc'
    ];

    // Relación con la tabla docentes
    public function datosDocente()
    {
        return $this->belongsTo(Docente::class, 'docente', 'codigo');
    }
}
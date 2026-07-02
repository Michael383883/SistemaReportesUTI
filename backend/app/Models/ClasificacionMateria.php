<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClasificacionMateria extends Model
{
    protected $table = 'CLASIFICACION_MATERIA';
    protected $primaryKey = 'ID_DETALLE';
    public $timestamps = false;

    protected $fillable = [
        'ID_CLASIFICACION',
        'COD_MATERIA',
        'NOMBRE_MATERIA',
        'COD_PLAN',        // ← Agregado (estaba en migración)
        'NOTA',            // ← Agregado (estaba en migración)
        'DETALLE',
        'ORDEN',
    ];

    protected $casts = [
        'ID_CLASIFICACION' => 'integer',
        'ORDEN' => 'integer',
        'NOTA' => 'integer',  // Si es nullable, puede ser null
    ];

    public function clasificacion()
    {
        return $this->belongsTo(ClasificacionDocente::class, 'ID_CLASIFICACION', 'ID_CLASIFICACION');
    }
}
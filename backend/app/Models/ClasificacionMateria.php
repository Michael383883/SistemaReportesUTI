<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClasificacionMateria extends Model
{
    protected $table = 'CLASIFICACION_MATERIA';

    protected $primaryKey = 'ID_DETALLE';

    public $timestamps = false;

    protected $fillable = [
        'ID_DOCUMENTO',
        'ID_CLASIFICACION_DOCENTE',
        'COD_MATERIA',
        'NOMBRE_MATERIA',
        'COD_PLAN',
        'GRUPO',
        'NOTA',
        'DETALLE',
        'ORDEN',
    ];

    protected $casts = [
        'ID_DOCUMENTO' => 'integer',
        'ID_CLASIFICACION_DOCENTE' => 'integer',
        'NOTA' => 'decimal:2',
        'ORDEN' => 'integer',
    ];

    public function documento()
    {
        return $this->belongsTo(
            ClasificacionDocumento::class,
            'ID_DOCUMENTO',
            'ID_DOCUMENTO'
        );
    }

    public function clasificacionDocente()
    {
        return $this->belongsTo(
            ClasificacionDocente::class,
            'ID_CLASIFICACION_DOCENTE',
            'ID_CLASIFICACION_DOCENTE'
        );
    }
}
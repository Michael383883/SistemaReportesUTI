<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResolucionDetalle extends Model
{
    protected $table = 'RESOLUCION_DETALLE';
    protected $primaryKey = 'ID_DETALLE';
    public $timestamps = false;

    protected $fillable = [
        'ID_RESOLUCION',
        'COD_DOCENTE',
        'COD_PLAN',
        'COD_MATERIA',
        'GRUPO',
        'TIPO',
        'OBSERVACION',
    ];

    public function resolucion()
    {
        return $this->belongsTo(
            ResolucionPdf::class,
            'ID_RESOLUCION',
            'ID_RESOLUCION'
        );
    }
}
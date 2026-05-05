<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResolucionDetalle extends Model
{
    protected $table = 'resolucion_detalle';
    protected $primaryKey = 'id_detalle';
    public $timestamps = false;

    protected $fillable = [
        'id_resolucion',
        'cod_docente',
        'cod_plan',
        'cod_materia',
        'grupo',
        'tipo',
        'observacion',
    ];

    public function resolucion()
    {
        return $this->belongsTo(ResolucionPdf::class, 'id_resolucion', 'id_resolucion');
    }
}

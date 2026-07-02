<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClasificacionReferencia extends Model
{
    protected $table = 'CLASIFICACION_REFERENCIA';
    protected $primaryKey = 'ID_REF';
    public $timestamps = false;

    protected $fillable = [
        'ID_CLASIFICACION',
        'NRO_REFERENCIA',
        'ID_RESOLUCION',
    ];

    protected $casts = [
        'ID_CLASIFICACION' => 'integer',
        'ID_RESOLUCION' => 'integer',
    ];

    public function clasificacion()
    {
        return $this->belongsTo(ClasificacionDocente::class, 'ID_CLASIFICACION', 'ID_CLASIFICACION');
    }

    public function resolucion()
    {
        return $this->belongsTo(ResolucionPdf::class, 'ID_RESOLUCION', 'ID_RESOLUCION');
    }
}
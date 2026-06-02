<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResolucionPdf extends Model
{
    protected $table = 'RESOLUCIONES_PDF';
    protected $primaryKey = 'ID_RESOLUCION';
    public $timestamps = false;

    protected $fillable = [
        'NRO_RESOLUCION',
        'DESCRIPCION',
        'ANIO',
        'PERIODO',
        'ARCHIVO_PDF',
        'NOMBRE_ARCHIVO',
        'TAMANIO_KB',
        'FECHA_SUBIDA',
        'SUBIDO_POR',
    ];

    protected $hidden = ['ARCHIVO_PDF'];

    public function detalles()
    {
        return $this->hasMany(
            ResolucionDetalle::class,
            'ID_RESOLUCION',
            'ID_RESOLUCION'
        );
    }
}
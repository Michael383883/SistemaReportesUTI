<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResolucionPdf extends Model
{
    protected $table = 'resoluciones_pdf';
    protected $primaryKey = 'id_resolucion';
    public $timestamps = false;

    protected $fillable = [
        'nro_resolucion',
        'descripcion',
        'anio',
        'periodo',
        'archivo_pdf',
        'nombre_archivo',
        'tamanio_kb',
        'fecha_subida',
        'subido_por',
    ];

    protected $hidden = ['archivo_pdf']; // Ocultarlo en listados

    public function detalles()
    {
        return $this->hasMany(ResolucionDetalle::class, 'id_resolucion', 'id_resolucion');
    }
}

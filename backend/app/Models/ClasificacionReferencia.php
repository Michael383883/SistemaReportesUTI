<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClasificacionReferencia extends Model
{
    protected $table = 'CLASIFICACION_REFERENCIA';

    protected $primaryKey = 'ID_REF';

    public $timestamps = false;

    protected $fillable = [
        'ID_DOCUMENTO',
        'NRO_REFERENCIA',
        'ID_RESOLUCION',
    ];

    protected $casts = [
        'ID_DOCUMENTO' => 'integer',
        'ID_RESOLUCION' => 'integer',
    ];

    public function documento()
    {
        return $this->belongsTo(
            ClasificacionDocumento::class,
            'ID_DOCUMENTO',
            'ID_DOCUMENTO'
        );
    }

    public function resolucion()
    {
        return $this->belongsTo(
            ResolucionPdf::class,
            'ID_RESOLUCION',
            'ID_RESOLUCION'
        );
    }
}
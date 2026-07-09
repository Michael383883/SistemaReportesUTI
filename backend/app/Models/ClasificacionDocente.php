<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClasificacionDocente extends Model
{
    protected $table = 'CLASIFICACION_DOCENTE';

    protected $primaryKey = 'ID_CLASIFICACION_DOCENTE';

    public $timestamps = false;

    protected $fillable = [
        'ID_DOCUMENTO',
        'COD_DOCENTE',
    ];

    protected $casts = [
        'ID_DOCUMENTO' => 'integer',
        'COD_DOCENTE' => 'integer',
    ];

    public function documento()
    {
        return $this->belongsTo(
            ClasificacionDocumento::class,
            'ID_DOCUMENTO',
            'ID_DOCUMENTO'
        );
    }

    public function docente()
    {
        return $this->belongsTo(
            Docente::class,
            'COD_DOCENTE',
            'CODIGO'
        );
    }

    public function materias()
    {
        return $this->hasMany(
            ClasificacionMateria::class,
            'ID_CLASIFICACION_DOCENTE',
            'ID_CLASIFICACION_DOCENTE'
        );
    }
}
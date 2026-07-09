<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClasificacionDocumento extends Model
{
    protected $table = 'CLASIFICACION_DOCUMENTO';

    protected $primaryKey = 'ID_DOCUMENTO';

    public $timestamps = false;

    const CREATED_AT = 'FECHA_REGISTRO';

    protected $fillable = [
        'TIPO_DOCUMENTO',
        'DETALLE_GENERAL',
        'CATEGORIA',
        'NIVEL',
        'GESTION',
        'PERIODO',
        'FOTOCOPIA_TITULAR',
        'RUTA_ARCHIVO',
        'NOMBRE_ARCHIVO',
        'OBSERVACION',
        'OBSERVACION2',
    ];

    protected $casts = [
        'FOTOCOPIA_TITULAR' => 'boolean',
        'FECHA_REGISTRO' => 'datetime',
    ];

    public function docentes()
    {
        return $this->hasMany(
            ClasificacionDocente::class,
            'ID_DOCUMENTO',
            'ID_DOCUMENTO'
        );
    }

    public function materias()
    {
        return $this->hasMany(
            ClasificacionMateria::class,
            'ID_DOCUMENTO',
            'ID_DOCUMENTO'
        );
    }

    public function referencias()
    {
        return $this->hasMany(
            ClasificacionReferencia::class,
            'ID_DOCUMENTO',
            'ID_DOCUMENTO'
        );
    }
}
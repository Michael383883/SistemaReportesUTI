<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClasificacionDocente extends Model
{
    protected $table = 'CLASIFICACION_DOCENTE';
    protected $primaryKey = 'ID_CLASIFICACION';
    public $timestamps = false;

    protected $fillable = [
        'COD_DOCENTE',
        'CATEGORIA',
        'NIVEL',
        'GESTION',
        'PERIODO',
        'DETALLE_GENERAL',
        'FOTOCOPIA_TITULAR',
        'RUTA_ARCHIVO',
        'NOMBRE_ARCHIVO',
        'OBSERVACION',
        'OBSERVACION2',
        'FECHA_REGISTRO',
    ];

    protected $casts = [
        'COD_DOCENTE' => 'integer',
        'FOTOCOPIA_TITULAR' => 'boolean',
        'FECHA_REGISTRO' => 'datetime',
    ];

    // NUEVO: relación con el docente (ajusta el nombre del modelo/campos si difieren)
    public function docente()
    {
        return $this->belongsTo(Docente::class, 'COD_DOCENTE', 'CODIGO');
    }

    public function materias()
    {
        return $this->hasMany(ClasificacionMateria::class, 'ID_CLASIFICACION', 'ID_CLASIFICACION')
            ->orderBy('ORDEN');
    }

    public function referencias()
    {
        return $this->hasMany(ClasificacionReferencia::class, 'ID_CLASIFICACION', 'ID_CLASIFICACION');
    }
}
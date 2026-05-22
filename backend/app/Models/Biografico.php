<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Biografico extends Model
{
    protected $table = 'biograficos';
    protected $primaryKey = 'codigo';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'codigo',
        'apellidos',
        'nombres',
        'email',
        'telefono',
        'direccion',
        'fecha_nacimiento',
        'dni'
    ];

    public $timestamps = false;
}
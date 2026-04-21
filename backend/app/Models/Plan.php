<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $table = 'planes';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = ['anio', 'codigo', 'nombre'];
}

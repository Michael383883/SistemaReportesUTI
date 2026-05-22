<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Horarios2 extends Model
{
    //
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Horarios2 extends Model
{
    use HasFactory;

    protected $table = 'horarios2';

    protected $fillable = [
        'anio',
        'periodo',
        'dia',
        'periodo_horario',
        'hora',
        'edificio',
        'piso',
        'ambiente',
        'materia',
        'grupo',
        'docente',
        'tipo',
        'periodo_hora',
    ];
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModulePermission extends Model
{
    protected $table = 'module_permissions';

    protected $fillable = ['module', 'role', 'enabled', 'updated_by'];

    protected $casts = ['enabled' => 'boolean'];

    // SQL Server con idioma/formato de fecha en español (dmy) interpreta
    // '2026-10-26' como año-día-mes y falla con "fuera de intervalo".
    // 'Ymd H:i:s' es el formato que SQL Server entiende en cualquier idioma.
    protected $dateFormat = 'Ymd H:i:s';

    /**
     * Catálogo de módulos que el admin puede habilitar/deshabilitar.
     * Para sumar otro módulo o rol a futuro basta agregarlo aquí.
     * (Ej.: para 'secretaria_talleres' => 'Secretaria de talleres')
     */
    public const MODULOS = [
        'kardex' => [
            'nombre' => 'Kardex de docentes',
            'descripcion' => 'Permite consultar el kardex de materias dictadas de un docente e imprimirlo (sin resolución ni designación). Solo lectura.',
            'roles' => [
                'secretaria' => 'Secretaria',
            ],
        ],
    ];

    /**
     * ¿El rol tiene habilitado el módulo?
     * - admin siempre pasa.
     * - Sin fila en la tabla = deshabilitado.
     */
    public static function habilitado(string $module, ?string $role): bool
    {
        if ($role === 'admin') {
            return true;
        }

        if (!$role) {
            return false;
        }

        return (bool) static::where('module', $module)
            ->where('role', $role)
            ->value('enabled');
    }
}
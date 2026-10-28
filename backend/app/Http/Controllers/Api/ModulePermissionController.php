<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ModulePermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ModulePermissionController extends Controller
{
    private function soloAdmin(Request $request): void
    {
        abort_unless(($request->user()->role ?? null) === 'admin', 403, 'Solo el administrador puede gestionar permisos.');
    }

    public function index(Request $request): JsonResponse
    {
        $this->soloAdmin($request);

        $guardados = ModulePermission::all()
            ->mapWithKeys(fn($p) => [$p->module . '|' . $p->role => (bool) $p->enabled]);

        $modulos = [];
        foreach (ModulePermission::MODULOS as $clave => $def) {
            $roles = [];
            foreach ($def['roles'] as $role => $label) {
                $roles[] = [
                    'role' => $role,
                    'label' => $label,
                    'enabled' => $guardados[$clave . '|' . $role] ?? false,
                ];
            }
            $modulos[] = [
                'clave' => $clave,
                'nombre' => $def['nombre'],
                'descripcion' => $def['descripcion'],
                'roles' => $roles,
            ];
        }

        return response()->json(['success' => true, 'modulos' => $modulos]);
    }

    public function update(Request $request): JsonResponse
    {
        $this->soloAdmin($request);

        $data = $request->validate([
            'module' => 'required|string|in:' . implode(',', array_keys(ModulePermission::MODULOS)),
            'role' => 'required|string',
            'enabled' => 'required|boolean',
        ]);

        $rolesValidos = array_keys(ModulePermission::MODULOS[$data['module']]['roles']);
        if (!in_array($data['role'], $rolesValidos, true)) {
            return response()->json(['success' => false, 'message' => 'Rol no configurable para este módulo.'], 422);
        }

        $permiso = ModulePermission::updateOrCreate(
            ['module' => $data['module'], 'role' => $data['role']],
            ['enabled' => (bool) $data['enabled'], 'updated_by' => $request->user()->id]
        );

        return response()->json([
            'success' => true,
            'module' => $permiso->module,
            'role' => $permiso->role,
            'enabled' => (bool) $permiso->enabled,
        ]);
    }
}
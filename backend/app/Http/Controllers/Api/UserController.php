<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * HU3 — Listar usuarios
     * GET /api/users
     */
    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->when($request->role,   fn ($q) => $q->where('role',   $request->role))
            ->when($request->active, fn ($q) => $q->where('active', filter_var($request->active, FILTER_VALIDATE_BOOLEAN)))
            ->orderBy('name')
            ->get()
            ->map(fn ($u) => $this->formatUser($u));

        return response()->json(['data' => $users]);
    }

    /**
     * HU3 — Crear usuario
     * POST /api/users
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => $request->password,   // el cast 'hashed' encripta automático
            'role'     => $request->role,
            'active'   => $request->boolean('active', true),
        ]);

        return response()->json([
            'message' => 'Usuario creado correctamente.',
            'data'    => $this->formatUser($user),
        ], 201);
    }

    /**
     * HU3 — Mostrar un usuario
     * GET /api/users/{user}
     */
    public function show(User $user): JsonResponse
    {
        return response()->json(['data' => $this->formatUser($user)]);
    }

    /**
     * HU3 — Actualizar usuario
     * PUT /api/users/{user}
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $data = $request->only(['name', 'email', 'role', 'active']);

        // Solo actualiza contraseña si viene en el request
        if ($request->filled('password')) {
            $data['password'] = $request->password;
        }

        $user->update($data);

        return response()->json([
            'message' => 'Usuario actualizado correctamente.',
            'data'    => $this->formatUser($user->fresh()),
        ]);
    }

    /**
     * HU3 — Eliminar usuario
     * DELETE /api/users/{user}
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        // Evita que el admin se elimine a sí mismo
        if ($request->user()->id === $user->id) {
            return response()->json([
                'message' => 'No puede eliminar su propia cuenta.',
            ], 422);
        }

        // Revoca todos los tokens del usuario eliminado
        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'Usuario eliminado correctamente.'], 200);
    }

    // ── Formato consistente ──────────────────────────────────────
    private function formatUser(User $user): array
    {
        return [
            'id'         => $user->id,
            'name'       => $user->name,
            'email'      => $user->email,
            'role'       => $user->role,
            'active'     => $user->active,
            'created_at' => $user->created_at?->format('Y-m-d'),
        ];
    }
}

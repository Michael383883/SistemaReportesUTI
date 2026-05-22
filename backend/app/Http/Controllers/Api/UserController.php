<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * GET /api/users
     */
    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->when($request->role, fn($q) => $q->where('role', $request->role))
            ->when(
                $request->has('active'),
                fn($q) => $q->where('active', filter_var($request->active, FILTER_VALIDATE_BOOLEAN))
            )
            ->orderBy('name')
            ->get()
            ->map(fn($u) => $this->formatUser($u));

        return response()->json(['data' => $users]);
    }

    /**
     * POST /api/users
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => ['required', Rule::in(['admin', 'secretaria', 'secretaria_talleres', 'uti'])],
            'active' => 'boolean',
        ]);

        $active = $request->boolean('active', true) ? 1 : 0;

        DB::statement("
            INSERT INTO users (name, email, password, role, active, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, GETDATE(), GETDATE())
        ", [
            $data['name'],
            $data['email'],
            Hash::make($data['password']),
            $data['role'],
            $active,
        ]);

        $user = User::where('email', $data['email'])->first();

        return response()->json([
            'message' => 'Usuario creado correctamente.',
            'data' => $this->formatUser($user),
        ], 201);
    }

    /**
     * GET /api/users/{user}
     */
    public function show(User $user): JsonResponse
    {
        return response()->json(['data' => $this->formatUser($user)]);
    }

    /**
     * PUT /api/users/{user}
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'sometimes|string|min:8',
            'role' => ['sometimes', Rule::in(['admin', 'secretaria', 'secretaria_talleres', 'uti'])],
            'active' => 'sometimes|boolean',
        ]);

        // Construye el SET dinámicamente
        $sets = ['updated_at = GETDATE()'];
        $params = [];

        if (isset($data['name'])) {
            $sets[] = 'name = ?';
            $params[] = $data['name'];
        }
        if (isset($data['email'])) {
            $sets[] = 'email = ?';
            $params[] = $data['email'];
        }
        if ($request->filled('password')) {
            $sets[] = 'password = ?';
            $params[] = Hash::make($data['password']);
        }
        if (isset($data['role'])) {
            $sets[] = 'role = ?';
            $params[] = $data['role'];
        }
        if (isset($data['active'])) {
            $sets[] = 'active = ?';
            $params[] = $data['active'] ? 1 : 0;
        }

        $params[] = $user->id;

        DB::statement('UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = ?', $params);

        $updated = User::find($user->id);

        return response()->json([
            'message' => 'Usuario actualizado correctamente.',
            'data' => $this->formatUser($updated),
        ]);
    }

    /**
     * DELETE /api/users/{user}
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($request->user()->id === $user->id) {
            return response()->json([
                'message' => 'No puede eliminar su propia cuenta.',
            ], 422);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'Usuario eliminado correctamente.']);
    }

    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'active' => (bool) $user->active,
            'created_at' => $user->created_at
                ? \Carbon\Carbon::parse($user->created_at)->format('Y-m-d')
                : null,
        ];
    }
}
<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken as SanctumToken;

class PersonalAccessToken extends SanctumToken
{
    public $timestamps = false;

    public static function boot(): void
    {
        parent::boot();

        // Intercepta INSERT
        static::creating(function ($token) {
            $abilities = is_array($token->abilities)
                ? json_encode($token->abilities)
                : ($token->abilities ?? '["*"]');

            $id = DB::selectOne("
                INSERT INTO personal_access_tokens
                    (tokenable_type, tokenable_id, name, token, abilities, last_used_at, expires_at, created_at, updated_at)
                OUTPUT INSERTED.id
                VALUES (?, ?, ?, ?, ?, NULL, NULL, GETDATE(), GETDATE())
            ", [
                $token->tokenable_type,
                $token->tokenable_id,
                $token->name,
                $token->token,
                $abilities,
            ]);

            $token->id = $id->id;

            return false;
        });

        // Intercepta UPDATE (last_used_at y cualquier otro campo)
        static::updating(function ($token) {
            $sets = [];
            $params = [];

            if ($token->isDirty('last_used_at')) {
                $sets[] = 'last_used_at = GETDATE()';
            }
            if ($token->isDirty('expires_at')) {
                $sets[] = 'expires_at = ?';
                $params[] = $token->expires_at;
            }

            if (empty($sets)) {
                return false;
            }

            $params[] = $token->id;

            DB::statement(
                'UPDATE personal_access_tokens SET ' . implode(', ', $sets) . ' WHERE id = ?',
                $params
            );

            return false;
        });
    }
}
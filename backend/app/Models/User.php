<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'active' => 'boolean',
        ];
    }

    // Inyecta GETDATE() al insertar
    protected static function booted(): void
    {
        static::creating(function ($user) {
            DB::statement("
                UPDATE users SET created_at = GETDATE(), updated_at = GETDATE()
                WHERE id = ?
            ", [$user->id]);
        });
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
    public function isSecretaria(): bool
    {
        return $this->role === 'secretaria';
    }
    public function isSecretariaTalleres(): bool
    {
        return $this->role === 'secretaria_talleres';
    }
    public function isUTI(): bool
    {
        return $this->role === 'uti';
    }

    public function hasRole(string|array $roles): bool
    {
        return in_array($this->role, (array) $roles);
    }
}
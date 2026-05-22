<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        // Formato de fecha para SQL Server
        if (config('database.default') === 'sqlsrv') {
            DB::statement("SET DATEFORMAT ymd");
        }

        // Usa nuestro modelo personalizado para los tokens
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
    }
}
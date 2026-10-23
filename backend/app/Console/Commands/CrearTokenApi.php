<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CrearTokenApi extends Command
{
    protected $signature = 'api:token {nombre}';
    protected $description = 'Genera un token para un cliente de la API pública';

    public function handle()
    {
        $token = Str::random(48);

        DB::table('API_CLIENTES')->insert([
            'NOMBRE'     => $this->argument('nombre'),
            'TOKEN_HASH' => hash('sha256', $token),
            'ACTIVO'     => 1,
            'CREADO_EN'  => now()->format('Y-m-d H:i:s'),
        ]);

        $this->info('Token (guárdalo, no se vuelve a mostrar):');
        $this->line($token);
    }
}
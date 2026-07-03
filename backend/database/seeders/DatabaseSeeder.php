<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

use Database\Seeders\ResolucionDetalleSeeder;
use Database\Seeders\ResolucionPdfSeeder;
use Database\Seeders\ClasificacionDocenteSeeder;


class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'email' => 'admin@umss.edu',
                'name' => 'Administrador UTI',
                'password' => Hash::make('Admin1234!'),
                'role' => 'admin',
            ],
            [
                'email' => 'secretaria@umss.edu',
                'name' => 'Secretaría FCE',
                'password' => Hash::make('Secret1234!'),
                'role' => 'secretaria',
            ],
            [
                'email' => 'talleres@umss.edu',
                'name' => 'Secretaría Talleres',
                'password' => Hash::make('Talleres1234!'),
                'role' => 'secretaria_talleres',
            ],
            [
                'email' => 'uti@umss.edu',
                'name' => 'Técnico UTI',
                'password' => Hash::make('Uti12345!'),
                'role' => 'uti',
            ],
        ];

        foreach ($users as $user) {
            DB::statement("
                IF NOT EXISTS (SELECT 1 FROM users WHERE email = ?)
                BEGIN
                    INSERT INTO users (name, email, password, role, active, created_at, updated_at)
                    VALUES (?, ?, ?, ?, 1, GETDATE(), GETDATE())
                END
            ", [
                $user['email'],
                $user['name'],
                $user['email'],
                $user['password'],
                $user['role'],
            ]);
        }
        $this->call([
            ResolucionPdfSeeder::class,
            ResolucionDetalleSeeder::class,
            ClasificacionDocenteSeeder::class,

        ]);
    }
}
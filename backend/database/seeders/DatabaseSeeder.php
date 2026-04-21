<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin inicial del sistema
        User::firstOrCreate(
            ['email' => 'admin@umss.edu'],
            [
                'name' => 'Administrador UTI',
                'password' => Hash::make('Admin1234!'),
                'role' => 'admin',
                'active' => true,
            ]
        );

        // Usuario Secretaría de prueba
        User::firstOrCreate(
            ['email' => 'secretaria@umss.edu'],
            [
                'name' => 'Secretaría FCE',
                'password' => Hash::make('Secret1234!'),
                'role' => 'secretaria',
                'active' => true,
            ]
        );

        // Usuario UTI de prueba
        User::firstOrCreate(
            ['email' => 'uti@umss.edu'],
            [
                'name' => 'Técnico UTI',
                'password' => Hash::make('Uti12345!'),
                'role' => 'uti',
                'active' => true,
            ]
        );
    }
}

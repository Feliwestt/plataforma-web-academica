<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class RoleAndUserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Crear los roles
        $roleAdmin = Role::create(['name' => 'Administrador']);
        $roleDirector = Role::create(['name' => 'Director']);
        $roleProfesor = Role::create(['name' => 'Profesor Jefe']);

        // 2. Crear usuario Administrador
        $admin = User::factory()->create([
            'name' => 'Admin Sistema',
            'email' => 'admin@sapecss.cl',
            'password' => Hash::make('password123'),
        ]);
        $admin->assignRole($roleAdmin);

        // 3. Crear usuario Director
        $director = User::factory()->create([
            'name' => 'Director San Sebastián',
            'email' => 'director@sapecss.cl',
            'password' => Hash::make('password123'),
        ]);
        $director->assignRole($roleDirector);

        // 4. Crear usuario Profesor
        $profesor = User::factory()->create([
            'name' => 'Profesor Juan Pérez',
            'email' => 'profesor@sapecss.cl',
            'password' => Hash::make('password123'),
        ]);
        $profesor->assignRole($roleProfesor);
    }
}
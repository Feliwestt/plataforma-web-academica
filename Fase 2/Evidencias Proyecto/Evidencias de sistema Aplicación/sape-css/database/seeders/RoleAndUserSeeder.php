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
        // 1. Crear los roles oficiales según el EPT
        $roleAdmin = Role::create(['name' => 'Administrador']);
        $roleProfJefe = Role::create(['name' => 'Profesor Jefe']);
        $roleProfAsignatura = Role::create(['name' => 'Profesor de Asignatura']);

        // 2. Crear usuario Administrador (Director / UTP)
        $admin = User::factory()->create([
            'name' => 'Director San Sebastián',
            'email' => 'director@colegio.cl',
            'password' => Hash::make('password123'),
        ]);
        $admin->assignRole($roleAdmin);

        // 3. Crear usuario Profesor Jefe (El que ya usabas)
        $profJefe = User::factory()->create([
            'name' => 'Carlos (Profe Jefe 1MA)',
            'email' => 'jefe1ma@colegio.cl',
            'password' => Hash::make('password123'),
        ]);
        $profJefe->assignRole($roleProfJefe);

        // 4. Crear usuario Profesor de Asignatura (La que ya usabas)
        $profAsignatura = User::factory()->create([
            'name' => 'Ana (Profe Matemáticas)',
            'email' => 'ana@colegio.cl',
            'password' => Hash::make('password123'),
        ]);
        $profAsignatura->assignRole($roleProfAsignatura);
    }
}
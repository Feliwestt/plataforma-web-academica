<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RoleAndUserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Crear los roles oficiales según el EPT
        $roleAdmin = Role::create(['name' => 'Administrador']);
        $roleProfJefe = Role::create(['name' => 'Profesor Jefe']);
        $roleProfAsignatura = Role::create(['name' => 'Profesor de Asignatura']);

        // 2. Crear usuario Administrador (Cubre a Director / UTP)
        $admin = User::factory()->create([
            'name' => 'Director San Sebastián',
            'email' => 'admin@sapecss.cl',
            'password' => Hash::make('password123'),
        ]);
        $admin->assignRole($roleAdmin);

        // 3. Crear usuario Profesor Jefe
        $profJefe = User::factory()->create([
            'name' => 'Profesor Juan Pérez (Jefatura)',
            'email' => 'profesor.jefe@sapecss.cl',
            'password' => Hash::make('password123'),
        ]);
        $profJefe->assignRole($roleProfJefe);

        // 4. Crear usuario Profesor de Asignatura
        $profAsignatura = User::factory()->create([
            'name' => 'Profesor Pedro Gómez (Asignatura)',
            'email' => 'profesor.asignatura@sapecss.cl',
            'password' => Hash::make('password123'),
        ]);
        $profAsignatura->assignRole($roleProfAsignatura);
    }
}

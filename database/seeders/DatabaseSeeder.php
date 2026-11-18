<?php

namespace Database\Seeders;

use App\Models\Curso;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Pedido;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Usuario demo de la Guía 10. firstOrCreate evita el UNIQUE de email
        // al volver a sembrar una base que ya tiene datos.
        User::firstOrCreate(
            ['email' => 'demo@continental.edu.pe'],
            ['name' => 'Docente Demo', 'password' => Hash::make('password')]
        );

        // Datos académicos: solo si la base aún no los tiene.
        if (Docente::count() === 0) {
            $docentes = Docente::factory(3)->create();
            $cursos = Curso::factory(6)->recycle($docentes)->create();

            Estudiante::factory(20)->create()->each(function (Estudiante $e) use ($cursos) {
                foreach ($cursos->random(2) as $curso) {
                    $e->cursos()->attach($curso->id, ['nota' => fake()->numberBetween(8, 20)]);
                }
            });
        }

        // Usuarios de la auditoría (Guía 11).
        $ana = User::firstOrCreate(
            ['email' => 'ana@test.pe'],
            ['name' => 'Ana', 'password' => Hash::make('secreto123')]
        );

        $beto = User::firstOrCreate(
            ['email' => 'beto@test.pe'],
            ['name' => 'Beto', 'password' => Hash::make('secreto123')]
        );

        Pedido::firstOrCreate(
            ['user_id' => $ana->id, 'descripcion' => 'Pedido de Ana'],
            ['total' => 50]
        );

        Pedido::firstOrCreate(
            ['user_id' => $beto->id, 'descripcion' => 'Pedido de Beto'],
            ['total' => 80]
        );
    }
}

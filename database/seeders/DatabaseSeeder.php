<?php

namespace Database\Seeders;

use App\Models\Curso;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Docente Demo',
            'email' => 'demo@continental.edu.pe',   // contraseña: password
        ]);

        $docentes = Docente::factory(3)->create();
        $cursos = Curso::factory(6)->recycle($docentes)->create();

        Estudiante::factory(20)->create()->each(function (Estudiante $e) use ($cursos) {
            foreach ($cursos->random(2) as $curso) {
                $e->cursos()->attach($curso->id, ['nota' => fake()->numberBetween(8, 20)]);
            }
        });
    }
}

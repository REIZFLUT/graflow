<?php

namespace Database\Seeders;

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
        User::factory()->create([
            'name' => 'Administrator',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        User::factory()->create([
            'name' => 'Produktmanager',
            'email' => 'productmanager@example.com',
            'password' => Hash::make('password'),
            'role' => 'productmanager',
        ]);

        User::factory()->create([
            'name' => 'Editor',
            'email' => 'editor@example.com',
            'password' => Hash::make('password'),
            'role' => 'editor',
        ]);

        User::factory()->create([
            'name' => 'Pia Maier',
            'email' => 'pia.maier@example.com',
            'password' => Hash::make('password'),
            'role' => 'author',
        ]);

        User::factory()->create([
            'name' => 'Dr. Thomas Brenner',
            'email' => 'thomas.brenner@example.com',
            'password' => Hash::make('password'),
            'role' => 'author',
        ]);

        User::factory()->create([
            'name' => 'Anna Schneider',
            'email' => 'anna.schneider@example.com',
            'password' => Hash::make('password'),
            'role' => 'author',
        ]);

        User::factory()->create([
            'name' => 'Prof. Michael Weber',
            'email' => 'michael.weber@example.com',
            'password' => Hash::make('password'),
            'role' => 'author',
        ]);

        User::factory()->create([
            'name' => 'Julia Hoffmann',
            'email' => 'julia.hoffmann@example.com',
            'password' => Hash::make('password'),
            'role' => 'author',
        ]);

        $this->call(DemoSeeder::class);
    }
}

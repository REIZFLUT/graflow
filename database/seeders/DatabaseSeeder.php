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
        $users = [
            ['name' => 'Administrator', 'email' => 'admin@example.com', 'role' => 'admin'],
            ['name' => 'Produktmanager', 'email' => 'productmanager@example.com', 'role' => 'productmanager'],
            ['name' => 'Editor', 'email' => 'editor@example.com', 'role' => 'editor'],
            ['name' => 'Lektor', 'email' => 'lector@example.com', 'role' => 'lector'],
            ['name' => 'Pia Maier', 'email' => 'pia.maier@example.com', 'role' => 'author'],
            ['name' => 'Dr. Thomas Brenner', 'email' => 'thomas.brenner@example.com', 'role' => 'author'],
            ['name' => 'Anna Schneider', 'email' => 'anna.schneider@example.com', 'role' => 'author'],
            ['name' => 'Prof. Michael Weber', 'email' => 'michael.weber@example.com', 'role' => 'author'],
            ['name' => 'Julia Hoffmann', 'email' => 'julia.hoffmann@example.com', 'role' => 'author'],
        ];

        foreach ($users as $user) {
            User::query()->updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => Hash::make('password'),
                    'role' => $user['role'],
                ],
            );
        }

        $this->call(DemoSeeder::class);
        $this->call(HandbookSeeder::class);
    }
}

<?php

namespace Database\Seeders;

use App\Models\Role;
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
        $adminRole = Role::query()->firstOrCreate(['name' => 'admin']);

        User::query()->updateOrCreate([
            'email' => 'admin@overshark.com',
        ], [
            'role_id' => $adminRole->id,
            'name' => 'Administrador',
            'password' => '123456789',
            'status' => true,
        ]);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use Carbon\Carbon;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        Role::insert([
            ['slug' => 'master', 'name' => 'Administrador Master', 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'embarcador', 'name' => 'Embarcador (Indústria/Transportadora)', 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'motorista', 'name' => 'Motorista Autônomo', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}
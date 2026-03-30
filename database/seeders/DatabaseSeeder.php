<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesSeeder::class,
            UserSeeder::class,
            ClientSeeder::class,
            ConditionSeeder::class,
            ElementTypeSeeder::class,
            AreaSeeder::class,
            ElementSeeder::class,
            ExecutionStatusSeeder::class,
            DemoUsersSeeder::class,
        ]);
    }
}
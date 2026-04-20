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
            
            ElementTypeSeeder::class,
            AreaSeeder::class,
            ElementSeeder::class,
            ConditionSeeder::class,
            ExecutionStatusSeeder::class,
            BeltDiagnosticSeeder::class,
            BeltComponentSeeder::class,
            BeltComponentDiagnosticSeeder::class,
            ElementComponentSeeder::class,
            DemoUsersSeeder::class,
            SystemModuleSeeder::class,
        ]);
    }
}
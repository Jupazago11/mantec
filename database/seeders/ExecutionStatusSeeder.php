<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExecutionStatusSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('execution_statuses')->insert([
            [
                'id' => 1,
                'code' => 'pending',
                'name' => 'PENDIENTE',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'code' => 'done',
                'name' => 'REALIZADO',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
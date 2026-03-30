<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Client;
use App\Models\ElementType;

class ElementTypeSeeder extends Seeder
{
    public function run(): void
    {
        $corona = Client::where('name', 'CORONA')->firstOrFail();

        ElementType::create([
            'client_id' => $corona->id,
            'name' => 'Banda transportadora',
            'description' => null,
            'status' => true,
        ]);
    }
}
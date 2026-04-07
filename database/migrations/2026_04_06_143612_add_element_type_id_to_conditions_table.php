<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Client;
use App\Models\ElementType;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conditions', function (Blueprint $table) {
            $table->foreignId('element_type_id')
                ->nullable()
                ->after('client_id')
                ->constrained('element_types')
                ->cascadeOnDelete();
        });

        // Backfill inicial:
        // Asumimos que las condiciones actuales de CORONA pertenecen a Banda transportadora
        $corona = Client::query()->where('name', 'CORONA')->first();
        $beltType = null;

        if ($corona) {
            $beltType = ElementType::query()
                ->where('client_id', $corona->id)
                ->where('name', 'Banda transportadora')
                ->first();
        }

        if ($beltType) {
            DB::table('conditions')
                ->where('client_id', $corona->id)
                ->update([
                    'element_type_id' => $beltType->id,
                ]);
        }

        Schema::table('conditions', function (Blueprint $table) {
            $table->foreignId('element_type_id')
                ->nullable(false)
                ->change();

            $table->dropUnique(['client_id', 'code']);
            $table->unique(['client_id', 'element_type_id', 'code'], 'conditions_client_type_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('conditions', function (Blueprint $table) {
            $table->dropUnique('conditions_client_type_code_unique');
            $table->unique(['client_id', 'code']);
            $table->dropConstrainedForeignId('element_type_id');
        });
    }
};

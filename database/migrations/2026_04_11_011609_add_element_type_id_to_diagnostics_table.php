<?php

use App\Models\Client;
use App\Models\ElementType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diagnostics', function (Blueprint $table) {
            $table->foreignId('element_type_id')
                ->nullable()
                ->after('client_id')
                ->constrained('element_types')
                ->cascadeOnDelete();
        });

        // Backfill temporal para no romper registros existentes.
        // Ajusta esta lógica si tienes más clientes/tipos reales.
        $corona = Client::query()->where('name', 'CORONA')->first();
        $beltType = null;

        if ($corona) {
            $beltType = ElementType::query()
                ->where('client_id', $corona->id)
                ->where('name', 'Banda transportadora')
                ->first();
        }

        if ($corona && $beltType) {
            DB::table('diagnostics')
                ->where('client_id', $corona->id)
                ->update([
                    'element_type_id' => $beltType->id,
                ]);
        }

        Schema::table('diagnostics', function (Blueprint $table) {
            $table->foreignId('element_type_id')
                ->nullable(false)
                ->change();

            $table->unique(
                ['client_id', 'element_type_id', 'name'],
                'diagnostics_client_type_name_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('diagnostics', function (Blueprint $table) {
            $table->dropUnique('diagnostics_client_type_name_unique');
            $table->dropConstrainedForeignId('element_type_id');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migra bitacora_entries.comment (un solo comentario que se sobrescribia
 * cada vez) hacia activity_employee_comments (historial, pedido
 * 2026-09-22). Cada fila con comment no nulo pasa a ser una entrada de
 * historial con activity_id = null (comentario de administrativo, a nivel
 * de dia — el mismo significado que ya tenia). Luego se elimina la
 * columna: Bitacora deja de escribir/leer comment desde bitacora_entries.
 *
 * down() re-crea la columna pero NO restaura los datos historicos exactos
 * (no hay forma de saber, sin guardar el dato aparte, cual fila de
 * activity_employee_comments vino de bitacora_entries vs se creo despues
 * de esta migracion) — limitacion aceptada, es un rollback de esquema, no
 * un respaldo de datos.
 */
return new class extends Migration
{
    public function up(): void
    {
        $entries = DB::table('bitacora_entries')
            ->whereNotNull('comment')
            ->where('comment', '!=', '')
            ->get(['employee_id', 'date', 'comment', 'corrected_by_employee_id', 'created_at', 'updated_at']);

        if ($entries->isNotEmpty()) {
            $employeeIds = $entries->pluck('corrected_by_employee_id')->filter()->unique()->values();
            $nombresPorId = DB::table('employees')
                ->whereIn('id', $employeeIds)
                ->pluck('nombre', 'id');

            $rows = $entries->map(function ($entry) use ($nombresPorId) {
                return [
                    'employee_id' => $entry->employee_id,
                    'date' => $entry->date,
                    'activity_id' => null,
                    'author_employee_id' => $entry->corrected_by_employee_id,
                    'author_name' => $entry->corrected_by_employee_id
                        ? ($nombresPorId[$entry->corrected_by_employee_id] ?? 'Administrativo')
                        : 'Superadmin',
                    'comment' => $entry->comment,
                    'created_at' => $entry->created_at,
                    'updated_at' => $entry->updated_at,
                ];
            })->all();

            DB::table('activity_employee_comments')->insert($rows);
        }

        Schema::table('bitacora_entries', function ($table) {
            $table->dropColumn('comment');
        });
    }

    public function down(): void
    {
        Schema::table('bitacora_entries', function ($table) {
            $table->text('comment')->nullable()->after('corrected_value');
        });
    }
};

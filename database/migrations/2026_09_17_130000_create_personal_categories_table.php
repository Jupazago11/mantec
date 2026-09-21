<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Nivel superior de la jerarquia "Rol -> Subrol" pedida por el usuario el
 * 2026-09-17: hasta ahora `employees.categoria` era un string fijo
 * (Rule::in(['Campo', 'Administrativos'])) sin ningun catalogo real detras
 * — no se podia crear una categoria nueva, ni archivar una existente. Esta
 * migracion la convierte en una tabla real (personal_categories, el "Rol"
 * en el lenguaje del usuario) y conecta personal_roles (el "Subrol") y
 * employees a ella por FK.
 *
 * Confirmado con el usuario: cualquier categoria puede tener subroles con
 * permisos (no solo "Administrativos") — no se restringe a nivel de
 * esquema, se deja a criterio de quien cree el subrol.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        $campoId = DB::table('personal_categories')->insertGetId([
            'name' => 'Campo',
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $administrativosId = DB::table('personal_categories')->insertGetId([
            'name' => 'Administrativos',
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // --- personal_roles (Subrol): se ancla a una categoria ---
        Schema::table('personal_roles', function (Blueprint $table) {
            $table->foreignId('personal_category_id')->nullable()->after('name')
                ->constrained('personal_categories')->nullOnDelete();
        });

        // Los subroles existentes (Administrativo, Supervisor) solo podian
        // asignarse a empleados de categoria "Administrativos" hasta hoy.
        DB::table('personal_roles')->update(['personal_category_id' => $administrativosId]);

        Schema::table('personal_roles', function (Blueprint $table) {
            $table->foreignId('personal_category_id')->nullable(false)->change();
        });

        // --- employees: categoria (string) -> personal_category_id (FK) ---
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('personal_category_id')->nullable()->after('categoria')
                ->constrained('personal_categories')->restrictOnDelete();
        });

        DB::table('employees')->where('categoria', 'Campo')->update(['personal_category_id' => $campoId]);
        DB::table('employees')->where('categoria', 'Administrativos')->update(['personal_category_id' => $administrativosId]);

        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('personal_category_id')->nullable(false)->change();
            $table->dropColumn('categoria');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('categoria', 20)->nullable()->after('nickname');
        });

        DB::table('employees')->update([
            'categoria' => DB::raw("(select name from personal_categories where personal_categories.id = employees.personal_category_id)"),
        ]);

        Schema::table('employees', function (Blueprint $table) {
            $table->string('categoria', 20)->nullable(false)->change();
            $table->dropConstrainedForeignId('personal_category_id');
        });

        Schema::table('personal_roles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('personal_category_id');
        });

        Schema::dropIfExists('personal_categories');
    }
};

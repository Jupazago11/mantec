<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_element_type_modules', function (Blueprint $table) {
            $table->foreignId('client_id')
                ->nullable()
                ->after('id')
                ->constrained('clients')
                ->cascadeOnDelete();

            $table->foreignId('element_type_id')
                ->nullable()
                ->after('client_id')
                ->constrained('element_types')
                ->cascadeOnDelete();

            $table->foreignId('system_module_id')
                ->nullable()
                ->after('element_type_id')
                ->constrained('system_modules')
                ->cascadeOnDelete();

            $table->boolean('module_enabled')
                ->default(false)
                ->after('system_module_id');

            $table->boolean('creation_enabled')
                ->default(false)
                ->after('module_enabled');

            $table->boolean('status')
                ->default(true)
                ->after('creation_enabled');
        });

        Schema::table('client_element_type_modules', function (Blueprint $table) {
            $table->unique(
                ['client_id', 'element_type_id', 'system_module_id'],
                'client_element_type_modules_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('client_element_type_modules', function (Blueprint $table) {
            $table->dropUnique('client_element_type_modules_unique');

            $table->dropConstrainedForeignId('system_module_id');
            $table->dropConstrainedForeignId('element_type_id');
            $table->dropConstrainedForeignId('client_id');

            $table->dropColumn([
                'module_enabled',
                'creation_enabled',
                'status',
            ]);
        });
    }
};
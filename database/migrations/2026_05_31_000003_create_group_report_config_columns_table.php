<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_report_config_columns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('config_id')
                ->constrained('group_report_configs')
                ->cascadeOnDelete();
            $table->string('column_key');
            $table->string('label');
            $table->unsignedTinyInteger('position');
            $table->boolean('visible')->default(true);
            $table->boolean('can_edit_admin_cliente')->default(false);
            $table->boolean('can_edit_observador')->default(false);
            $table->boolean('can_edit_observador_cliente')->default(false);
            $table->timestamps();

            $table->unique(['config_id', 'column_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_report_config_columns');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('band_event_drafts', function (Blueprint $table) {
            // REFERENCIA BANDA
            $table->decimal('total_thickness', 10, 2)->nullable();
            $table->decimal('top_cover_thickness', 10, 2)->nullable();
            $table->decimal('bottom_cover_thickness', 10, 2)->nullable();
            $table->integer('plies')->nullable();

            // VULCANIZADO
            $table->decimal('cooling_time', 10, 2)->nullable();

            // ENTREGA EQUIPO
            $table->decimal('motor_current', 10, 2)->nullable();
            $table->string('alignment')->nullable();
            $table->string('material_accumulation')->nullable();
            $table->string('guard')->nullable();
            $table->string('idler_condition')->nullable();

            // CAMBIO TRAMO
            $table->string('section_brand')->nullable();
            $table->decimal('section_thickness', 10, 2)->nullable();
            $table->integer('section_plies')->nullable();

            // LÓGICA
            $table->boolean('same_reference')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('band_event_drafts', function (Blueprint $table) {
            $table->dropColumn([
                'total_thickness',
                'top_cover_thickness',
                'bottom_cover_thickness',
                'plies',
                'cooling_time',
                'motor_current',
                'alignment',
                'material_accumulation',
                'guard',
                'idler_condition',
                'section_brand',
                'section_thickness',
                'section_plies',
                'same_reference',
            ]);
        });
    }
};
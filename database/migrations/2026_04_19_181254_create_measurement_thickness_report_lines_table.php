<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('measurement_thickness_report_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('report_id')
                ->constrained('measurement_thickness_reports')
                ->cascadeOnDelete();

            $table->unsignedInteger('cover_number');

            $table->decimal('top_left', 8, 2)->nullable();
            $table->decimal('top_center', 8, 2)->nullable();
            $table->decimal('top_right', 8, 2)->nullable();

            $table->decimal('bottom_left', 8, 2)->nullable();
            $table->decimal('bottom_center', 8, 2)->nullable();
            $table->decimal('bottom_right', 8, 2)->nullable();

            $table->decimal('hardness_left', 8, 2)->nullable();
            $table->decimal('hardness_center', 8, 2)->nullable();
            $table->decimal('hardness_right', 8, 2)->nullable();

            $table->timestamps();

            $table->unique(
                ['report_id', 'cover_number'],
                'measurement_thickness_report_lines_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('measurement_thickness_report_lines');
    }
};
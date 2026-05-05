<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('semaphore_template_column_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('semaphore_template_column_id')->constrained()->cascadeOnDelete();
            $table->foreignId('component_id')->constrained()->cascadeOnDelete();
            $table->foreignId('diagnostic_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(
                ['semaphore_template_column_id', 'component_id', 'diagnostic_id'],
                'semaphore_template_column_rules_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('semaphore_template_column_rules');
    }
};

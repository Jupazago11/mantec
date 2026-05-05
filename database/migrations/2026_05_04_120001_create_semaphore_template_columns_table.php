<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('semaphore_template_columns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('semaphore_template_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->string('label');
            $table->text('description')->nullable();
            $table->string('column_type', 50);
            $table->string('severity_direction', 10)->default('asc');
            $table->string('empty_state_behavior', 20)->default('neutral');
            $table->string('source_column_key')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique(['semaphore_template_id', 'key'], 'semaphore_template_columns_unique_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('semaphore_template_columns');
    }
};

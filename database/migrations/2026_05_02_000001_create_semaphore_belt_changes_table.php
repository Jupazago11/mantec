<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('semaphore_belt_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('element_id')->constrained()->cascadeOnDelete();
            $table->integer('year');
            $table->integer('week');
            $table->boolean('is_belt_change');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['element_id', 'year', 'week'], 'semaphore_belt_changes_unique');
            $table->index(['year', 'week'], 'semaphore_belt_changes_week_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('semaphore_belt_changes');
    }
};

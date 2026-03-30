<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('element_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('element_id')->constrained('elements')->cascadeOnDelete();
            $table->foreignId('component_id')->constrained('components')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['element_id', 'component_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('element_components');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('component_diagnostics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('component_id')->constrained('components')->cascadeOnDelete();
            $table->foreignId('diagnostic_id')->constrained('diagnostics')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['component_id', 'diagnostic_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('component_diagnostics');
    }
};
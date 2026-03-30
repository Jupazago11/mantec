<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('code', 50)->nullable();
            $table->foreignId('element_type_id')->constrained('element_types')->cascadeOnDelete();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_default')->default(false);
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique(['client_id', 'element_type_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('components');
    }
};
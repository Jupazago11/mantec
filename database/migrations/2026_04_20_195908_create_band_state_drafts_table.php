<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('band_state_drafts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('element_id')
                ->constrained('elements')
                ->cascadeOnDelete();

            $table->string('description')->nullable();
            $table->decimal('width', 10, 2)->nullable();
            $table->decimal('top_cover', 10, 2)->nullable();
            $table->decimal('bottom_cover', 10, 2)->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique('element_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('band_state_drafts');
    }
};
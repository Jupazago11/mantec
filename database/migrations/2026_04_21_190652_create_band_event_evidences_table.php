<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('band_event_evidences', function (Blueprint $table) {
            $table->id();

            $table->foreignId('band_event_id')
                ->constrained('band_events')
                ->cascadeOnDelete();

            $table->string('file_path');
            $table->string('file_type'); // image | video

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['band_event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('band_event_evidences');
    }
};
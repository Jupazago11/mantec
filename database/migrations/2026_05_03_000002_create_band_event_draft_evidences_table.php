<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('band_event_draft_evidences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('band_event_draft_id')
                ->constrained('band_event_drafts')
                ->cascadeOnDelete();
            $table->string('disk')->default('r2');
            $table->string('file_path');
            $table->string('file_type', 20);
            $table->string('file_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['band_event_draft_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('band_event_draft_evidences');
    }
};

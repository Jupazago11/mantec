<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('band_state_reports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('element_id')
                ->constrained('elements')
                ->cascadeOnDelete();

            $table->date('report_date');

            $table->string('description')->nullable();
            $table->decimal('width', 10, 2)->nullable();
            $table->decimal('top_cover', 10, 2)->nullable();
            $table->decimal('bottom_cover', 10, 2)->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            $table->index(['element_id', 'report_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('band_state_reports');
    }
};
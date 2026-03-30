<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_details', function (Blueprint $table) {
            $table->id();

            $table->foreignId('report_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->foreignId('element_id')->constrained()->cascadeOnDelete();
            $table->foreignId('component_id')->constrained()->cascadeOnDelete();
            $table->foreignId('diagnostic_id')->constrained()->cascadeOnDelete();

            $table->integer('year');
            $table->integer('week');

            $table->foreignId('condition_id')->constrained()->cascadeOnDelete();

            $table->text('observation')->nullable();
            $table->text('recommendation')->nullable();

            $table->string('orden')->nullable();
            $table->string('aviso')->nullable();

            $table->foreignId('execution_status_id')->nullable()->constrained('execution_statuses')->nullOnDelete();
            $table->date('execution_date')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_details');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_evidences', function (Blueprint $table) {
            $table->id();

            $table->foreignId('activity_id')
                ->constrained('activities')
                ->cascadeOnDelete();

            $table->foreignId('uploaded_by_employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();

            $table->string('disk', 50)->default('r2');
            $table->text('path');
            $table->string('original_name', 255);
            $table->string('stored_name', 255);
            $table->string('mime_type', 100);
            $table->string('extension', 20)->nullable();
            $table->string('file_type', 20); // image | video
            $table->unsignedBigInteger('size_bytes');
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index('activity_id');
            $table->index('uploaded_by_employee_id');
            $table->index('file_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_evidences');
    }
};

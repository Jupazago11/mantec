<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('elements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('area_id')->constrained('areas')->cascadeOnDelete();
            $table->foreignId('element_type_id')->constrained('element_types')->cascadeOnDelete();

            $table->string('name');
            $table->string('code')->nullable();
            $table->string('warehouse_code')->nullable();

            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        // Unicidad por cliente a través del area_id no se puede expresar simple con índice normal,
    }

    public function down(): void
    {
        Schema::dropIfExists('elements');
    }
};

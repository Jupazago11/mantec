<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_client_element_type_areas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('client_id')
                ->constrained('clients')
                ->cascadeOnDelete();

            $table->foreignId('element_type_id')
                ->constrained('element_types')
                ->cascadeOnDelete();

            $table->foreignId('area_id')
                ->constrained('areas')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(
                ['user_id', 'client_id', 'element_type_id', 'area_id'],
                'uceta_unique'
            );

            $table->index(['user_id', 'client_id'], 'uceta_user_client_idx');
            $table->index(['user_id', 'element_type_id'], 'uceta_user_type_idx');
            $table->index(['client_id', 'element_type_id', 'area_id'], 'uceta_client_type_area_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_client_element_type_areas');
    }
};

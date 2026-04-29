<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_client_group_areas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('client_id')
                ->constrained('clients')
                ->cascadeOnDelete();

            $table->foreignId('group_id')
                ->constrained('groups')
                ->cascadeOnDelete();

            $table->foreignId('area_id')
                ->constrained('areas')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(
                ['user_id', 'client_id', 'group_id', 'area_id'],
                'ucga_user_client_group_area_unique'
            );

            $table->index(['client_id', 'group_id'], 'ucga_client_group_index');
            $table->index(['user_id', 'client_id'], 'ucga_user_client_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_client_group_areas');
    }
};
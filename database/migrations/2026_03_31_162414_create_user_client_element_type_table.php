<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_client_element_type', function (Blueprint $table) {
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

            $table->timestamps();

            $table->unique(
                ['user_id', 'client_id', 'element_type_id'],
                'ucet_unique_user_client_element_type'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_client_element_type');
    }
};

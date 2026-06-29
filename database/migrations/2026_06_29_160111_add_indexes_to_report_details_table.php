<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_details', function (Blueprint $table) {
            // Filtro principal: activos accesibles + año + semana
            $table->index(['element_id', 'year', 'week'], 'rd_element_year_week');
            // Agregación de máx semana por elemento en fallback
            $table->index(['element_id', 'year'], 'rd_element_year');
        });
    }

    public function down(): void
    {
        Schema::table('report_details', function (Blueprint $table) {
            $table->dropIndex('rd_element_year_week');
            $table->dropIndex('rd_element_year');
        });
    }
};

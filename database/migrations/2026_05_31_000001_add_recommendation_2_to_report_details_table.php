<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_details', function (Blueprint $table) {
            $table->text('recommendation_2')->nullable()->after('recommendation');
        });
    }

    public function down(): void
    {
        Schema::table('report_details', function (Blueprint $table) {
            $table->dropColumn('recommendation_2');
        });
    }
};

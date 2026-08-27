<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('band_events', function (Blueprint $table) {
            $table->boolean('averia')->nullable();
        });

        Schema::table('band_event_drafts', function (Blueprint $table) {
            $table->boolean('averia')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('band_events', function (Blueprint $table) {
            $table->dropColumn('averia');
        });

        Schema::table('band_event_drafts', function (Blueprint $table) {
            $table->dropColumn('averia');
        });
    }
};

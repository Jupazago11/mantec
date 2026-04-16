<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_details', function (Blueprint $table) {
            $table->boolean('status')->nullable()->after('execution_date');
        });

        DB::table('report_details')
            ->whereNull('status')
            ->update(['status' => true]);

        Schema::table('report_details', function (Blueprint $table) {
            $table->boolean('status')->default(true)->nullable(false)->change();
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('report_details', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });
    }
};
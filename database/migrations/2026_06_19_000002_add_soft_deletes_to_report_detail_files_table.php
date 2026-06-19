<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_detail_files', function (Blueprint $table) {
            $table->foreignId('detached_by')
                ->nullable()
                ->after('sort_order')
                ->constrained('users')
                ->nullOnDelete();

            $table->softDeletes()->after('detached_by');
        });
    }

    public function down(): void
    {
        Schema::table('report_detail_files', function (Blueprint $table) {
            $table->dropConstrainedForeignId('detached_by');
            $table->dropSoftDeletes();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_detail_files', function (Blueprint $table) {
            $table->string('evidence_kind', 30)
                ->default('hallazgo')
                ->after('file_type');

            $table->index('evidence_kind');
        });

        DB::table('report_detail_files')
            ->whereNull('evidence_kind')
            ->update(['evidence_kind' => 'hallazgo']);
    }

    public function down(): void
    {
        Schema::table('report_detail_files', function (Blueprint $table) {
            $table->dropIndex(['evidence_kind']);
            $table->dropColumn('evidence_kind');
        });
    }
};

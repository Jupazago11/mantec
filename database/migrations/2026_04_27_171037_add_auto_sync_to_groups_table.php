<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table
                ->boolean('auto_sync')
                ->default(false)
                ->after('description');
        });

        DB::table('groups')->update([
            'auto_sync' => false,
        ]);
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn('auto_sync');
        });
    }
};
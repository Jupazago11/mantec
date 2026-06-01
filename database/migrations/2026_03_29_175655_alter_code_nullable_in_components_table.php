<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('components', function (Blueprint $table) {
            $table->string('code')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('components', function (Blueprint $table) {
            $table->string('code')->nullable(false)->change();
        });
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('band_event_evidences', function (Blueprint $table) {
            $table->string('disk')->default('r2')->after('band_event_id');
            $table->string('file_name')->nullable()->after('file_type');
            $table->string('mime_type')->nullable()->after('file_name');
            $table->unsignedBigInteger('size_bytes')->default(0)->after('mime_type');
            $table->unsignedInteger('sort_order')->default(0)->after('size_bytes');
        });
    }

    public function down(): void
    {
        Schema::table('band_event_evidences', function (Blueprint $table) {
            $table->dropColumn([
                'disk',
                'file_name',
                'mime_type',
                'size_bytes',
                'sort_order',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_page_custom_pages', function (Blueprint $table) {
            // Structured marketing blocks (hero, features, showcase, stats, cta).
            // When present the page renders through the marketing template instead
            // of the plain prose one; `content` stays as the fallback body.
            $table->json('page_data')->nullable()->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('landing_page_custom_pages', function (Blueprint $table) {
            $table->dropColumn('page_data');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_page_custom_pages', function (Blueprint $table) {
            // null = standalone page, otherwise the header dropdown it belongs to
            $table->string('nav_group')->nullable()->after('slug');
            $table->string('summary')->nullable()->after('content');
            $table->string('icon')->nullable()->after('summary');
        });
    }

    public function down(): void
    {
        Schema::table('landing_page_custom_pages', function (Blueprint $table) {
            $table->dropColumn(['nav_group', 'summary', 'icon']);
        });
    }
};

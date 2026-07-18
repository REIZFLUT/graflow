<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('article_versions', function (Blueprint $table) {
            if (! Schema::hasColumn('article_versions', 'status')) {
                $table->string('status')->nullable()->after('content');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('article_versions', function (Blueprint $table) {
            if (Schema::hasColumn('article_versions', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};

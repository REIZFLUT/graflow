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
        Schema::table('articles', function (Blueprint $table) {
            $table->unsignedInteger('position')->default(1);
            $table->index(
                ['publication_issue_id', 'position'],
                'articles_issue_position_index',
            );
            $table->index(
                ['publication_chapter_id', 'position'],
                'articles_chapter_position_index',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropIndex('articles_issue_position_index');
            $table->dropIndex('articles_chapter_position_index');
            $table->dropColumn('position');
        });
    }
};

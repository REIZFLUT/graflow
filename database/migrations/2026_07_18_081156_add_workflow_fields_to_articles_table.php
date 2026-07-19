<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('articles')->where('status', 'draft')->update(['status' => 'authoring']);
        DB::table('articles')->where('status', 'archived')->update(['status' => 'published']);
        DB::table('article_versions')->where('status', 'draft')->update(['status' => 'authoring']);
        DB::table('article_versions')->where('status', 'archived')->update(['status' => 'published']);

        Schema::table('articles', function (Blueprint $table) {
            $table->string('status')->default('authoring')->change();
            $table->foreignId('product_manager_id')
                ->nullable()
                ->after('owner_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('author_id')
                ->nullable()
                ->after('product_manager_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('current_assignee_id')
                ->nullable()
                ->after('author_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('publication_chapter_id')
                ->nullable()
                ->after('publication_issue_id')
                ->constrained('publication_chapters')
                ->nullOnDelete();
            $table->timestamp('submission_deadline')->nullable()->after('publication_chapter_id')->index();
            $table->unsignedInteger('target_character_count')->nullable()->after('submission_deadline');
            $table->timestamp('published_at')->nullable()->after('target_character_count')->index();
        });

        DB::table('articles')
            ->select(['id', 'owner_id', 'publication_issue_id', 'status'])
            ->orderBy('id')
            ->eachById(function (object $article): void {
                $ownerRole = DB::table('users')
                    ->where('id', $article->owner_id)
                    ->value('role');
                $productManagerId = DB::table('publication_issues')
                    ->join('publications', 'publications.id', '=', 'publication_issues.publication_id')
                    ->join('users', 'users.id', '=', 'publications.owner_id')
                    ->where('publication_issues.id', $article->publication_issue_id)
                    ->where('users.role', 'productmanager')
                    ->value('publications.owner_id');

                if ($ownerRole === 'productmanager') {
                    $productManagerId = $article->owner_id;
                }

                $authorId = $ownerRole === 'author' ? $article->owner_id : null;
                $currentAssigneeId = $article->status === 'authoring' ? $authorId : null;

                DB::table('articles')
                    ->where('id', $article->id)
                    ->update([
                        'product_manager_id' => $productManagerId,
                        'author_id' => $authorId,
                        'current_assignee_id' => $currentAssigneeId,
                        'published_at' => $article->status === 'published' ? now() : null,
                    ]);

                DB::table('article_participants')->insertOrIgnore([
                    'article_id' => $article->id,
                    'user_id' => $article->owner_id,
                    'process_role' => $ownerRole,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if ($productManagerId !== null && $productManagerId !== $article->owner_id) {
                    DB::table('article_participants')->insertOrIgnore([
                        'article_id' => $article->id,
                        'user_id' => $productManagerId,
                        'process_role' => 'productmanager',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('articles')->where('status', 'authoring')->update(['status' => 'draft']);
        DB::table('article_versions')->where('status', 'authoring')->update(['status' => 'draft']);

        Schema::table('articles', function (Blueprint $table) {
            $table->string('status')->default('draft')->change();
            $table->dropConstrainedForeignId('product_manager_id');
            $table->dropConstrainedForeignId('author_id');
            $table->dropConstrainedForeignId('current_assignee_id');
            $table->dropConstrainedForeignId('publication_chapter_id');
            $table->dropColumn([
                'submission_deadline',
                'target_character_count',
                'published_at',
            ]);
        });
    }
};

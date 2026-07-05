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
            if (! Schema::hasColumn('articles', 'publication_issue_id')) {
                $table->foreignId('publication_issue_id')
                    ->nullable()
                    ->after('status')
                    ->constrained('publication_issues')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('articles', 'metadata')) {
                $table->json('metadata')->nullable()->after('publication_issue_id');
            }
        });

        if (
            Schema::hasColumn('articles', 'publication_issue_id')
            && ! $this->foreignKeyExists('articles', 'articles_publication_issue_id_foreign')
        ) {
            Schema::table('articles', function (Blueprint $table) {
                $table->foreign('publication_issue_id')
                    ->references('id')
                    ->on('publication_issues')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            if ($this->foreignKeyExists('articles', 'articles_publication_issue_id_foreign')) {
                $table->dropForeign(['publication_issue_id']);
            }

            if (Schema::hasColumn('articles', 'metadata')) {
                $table->dropColumn('metadata');
            }

            if (Schema::hasColumn('articles', 'publication_issue_id')) {
                $table->dropColumn('publication_issue_id');
            }
        });
    }

    private function foreignKeyExists(string $table, string $name): bool
    {
        return collect(Schema::getForeignKeys($table))
            ->contains(fn (array $foreignKey): bool => $foreignKey['name'] === $name);
    }
};

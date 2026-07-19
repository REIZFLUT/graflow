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
        Schema::create('article_comment_threads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_id')->constrained('users');
            $table->text('anchor_text')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by_id')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_comment_threads');
    }
};

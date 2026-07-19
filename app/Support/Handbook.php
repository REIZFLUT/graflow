<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\Publication;
use App\Models\PublicationIssue;
use App\Models\User;

class Handbook
{
    public static function name(): string
    {
        return (string) config('handbook.name');
    }

    public static function issueLabel(): string
    {
        return (string) config('handbook.issue_label');
    }

    /**
     * Determine whether the given article belongs to the handbook issue.
     * Matches by publication name and issue label without creating anything.
     */
    public static function containsArticle(\App\Models\Article $article): bool
    {
        $article->loadMissing('publicationIssue.publication');

        $issue = $article->publicationIssue;

        return $issue !== null
            && $issue->label === static::issueLabel()
            && $issue->publication?->name === static::name();
    }

    /**
     * Resolve the handbook publication, creating it (owned by an administrator)
     * when it does not exist yet. Returns null when no administrator exists to
     * own the publication.
     */
    public static function resolvePublication(): ?Publication
    {
        $publication = Publication::query()
            ->where('name', static::name())
            ->first();

        if ($publication !== null) {
            return $publication;
        }

        $admin = User::query()
            ->where('role', UserRole::Admin)
            ->orderBy('id')
            ->first();

        if ($admin === null) {
            return null;
        }

        return Publication::query()->create([
            'name' => static::name(),
            'owner_id' => $admin->id,
        ]);
    }

    /**
     * Resolve the single handbook issue that stores all handbook articles.
     * Returns null when the handbook publication cannot be provisioned.
     */
    public static function resolveIssue(): ?PublicationIssue
    {
        $publication = static::resolvePublication();

        if ($publication === null) {
            return null;
        }

        return $publication->issues()->firstOrCreate([
            'label' => static::issueLabel(),
        ]);
    }
}

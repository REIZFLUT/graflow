<?php

namespace App\Models;

use App\Enums\ArticleStatus;
use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $title
 * @property array<string, mixed>|null $content
 * @property int $owner_id
 * @property ArticleStatus $status
 * @property int|null $publication_issue_id
 * @property int|null $editor_settings_set_id
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $owner
 * @property-read PublicationIssue|null $publicationIssue
 * @property-read Collection<int, PublicationCategory> $publicationCategories
 * @property-read Collection<int, ArticleVersion> $versions
 * @property-read Collection<int, ArticleMedia> $media
 * @property-read Collection<int, ArticlePdf> $pdfs
 */
class Article extends Model
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'content',
        'owner_id',
        'status',
        'publication_issue_id',
        'editor_settings_set_id',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'content' => 'array',
            'status' => ArticleStatus::class,
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return HasMany<ArticleVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(ArticleVersion::class);
    }

    /**
     * @return HasMany<ArticleMedia, $this>
     */
    public function media(): HasMany
    {
        return $this->hasMany(ArticleMedia::class);
    }

    /**
     * @return HasMany<ArticlePdf, $this>
     */
    public function pdfs(): HasMany
    {
        return $this->hasMany(ArticlePdf::class);
    }

    /**
     * @return BelongsTo<PublicationIssue, $this>
     */
    public function publicationIssue(): BelongsTo
    {
        return $this->belongsTo(PublicationIssue::class);
    }

    /**
     * @return BelongsTo<EditorSettingsSet, $this>
     */
    public function editorSettingsSet(): BelongsTo
    {
        return $this->belongsTo(EditorSettingsSet::class);
    }

    /**
     * @return BelongsToMany<PublicationCategory, $this>
     */
    public function publicationCategories(): BelongsToMany
    {
        return $this->belongsToMany(PublicationCategory::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (Article $article): void {
            $disk = Storage::disk(config('article-media.disk'));
            $directory = "articles/{$article->id}";

            if ($disk->exists($directory)) {
                $disk->deleteDirectory($directory);
            }
        });
    }
}

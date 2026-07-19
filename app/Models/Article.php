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
 * @property int|null $product_manager_id
 * @property int|null $author_id
 * @property int|null $current_assignee_id
 * @property ArticleStatus $status
 * @property int|null $publication_issue_id
 * @property int|null $publication_chapter_id
 * @property int $position
 * @property int|null $editor_settings_set_id
 * @property Carbon|null $submission_deadline
 * @property int|null $target_character_count
 * @property Carbon|null $published_at
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $owner
 * @property-read User|null $productManager
 * @property-read User|null $author
 * @property-read User|null $currentAssignee
 * @property-read PublicationIssue|null $publicationIssue
 * @property-read PublicationChapter|null $publicationChapter
 * @property-read Collection<int, PublicationCategory> $publicationCategories
 * @property-read Collection<int, ArticleParticipant> $participants
 * @property-read Collection<int, User> $participantUsers
 * @property-read Collection<int, ArticleWorkflowEvent> $workflowEvents
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
        'product_manager_id',
        'author_id',
        'current_assignee_id',
        'status',
        'publication_issue_id',
        'publication_chapter_id',
        'position',
        'editor_settings_set_id',
        'submission_deadline',
        'target_character_count',
        'published_at',
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
            'position' => 'integer',
            'submission_deadline' => 'datetime',
            'target_character_count' => 'integer',
            'published_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function productManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'product_manager_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function currentAssignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_assignee_id');
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
     * @return BelongsTo<PublicationChapter, $this>
     */
    public function publicationChapter(): BelongsTo
    {
        return $this->belongsTo(PublicationChapter::class);
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

    /**
     * @return HasMany<ArticleParticipant, $this>
     */
    public function participants(): HasMany
    {
        return $this->hasMany(ArticleParticipant::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function participantUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'article_participants')
            ->withPivot('process_role')
            ->withTimestamps();
    }

    /**
     * @return HasMany<ArticleWorkflowEvent, $this>
     */
    public function workflowEvents(): HasMany
    {
        return $this->hasMany(ArticleWorkflowEvent::class);
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

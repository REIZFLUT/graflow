<?php

namespace App\Models;

use Database\Factories\ArticleCommentThreadFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property int $article_id
 * @property int $created_by_id
 * @property string|null $anchor_text
 * @property Carbon|null $resolved_at
 * @property int|null $resolved_by_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Article $article
 * @property-read User $createdBy
 * @property-read User|null $resolvedBy
 * @property-read Collection<int, ArticleComment> $comments
 * @property-read bool $is_resolved
 */
class ArticleCommentThread extends Model
{
    /** @use HasFactory<ArticleCommentThreadFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * @var string
     */
    protected $keyType = 'string';

    /**
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'article_id',
        'created_by_id',
        'anchor_text',
        'resolved_at',
        'resolved_by_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Article, $this>
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_id');
    }

    /**
     * @return HasMany<ArticleComment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(ArticleComment::class, 'thread_id')
            ->orderBy('created_at');
    }

    public function getIsResolvedAttribute(): bool
    {
        return $this->resolved_at !== null;
    }
}

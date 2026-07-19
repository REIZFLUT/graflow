<?php

namespace App\Models;

use App\Enums\ArticleStatus;
use Database\Factories\ArticleWorkflowEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * @property int $id
 * @property int $article_id
 * @property ArticleStatus|null $from_status
 * @property ArticleStatus|null $to_status
 * @property int $actor_id
 * @property int|null $assignee_id
 * @property string|null $reason
 * @property Carbon $created_at
 * @property-read Article $article
 * @property-read User $actor
 * @property-read User|null $assignee
 */
class ArticleWorkflowEvent extends Model
{
    /** @use HasFactory<ArticleWorkflowEventFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'article_id',
        'from_status',
        'to_status',
        'actor_id',
        'assignee_id',
        'reason',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'from_status' => ArticleStatus::class,
            'to_status' => ArticleStatus::class,
            'created_at' => 'datetime',
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
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Article workflow events are append-only.');
        });

        static::deleting(function (): never {
            throw new LogicException('Article workflow events are append-only.');
        });
    }
}

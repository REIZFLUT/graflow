<?php

namespace App\Models;

use Database\Factories\ArticleCommentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $thread_id
 * @property int $user_id
 * @property string $body
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ArticleCommentThread $thread
 * @property-read User $user
 */
class ArticleComment extends Model
{
    /** @use HasFactory<ArticleCommentFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'thread_id',
        'user_id',
        'body',
    ];

    /**
     * @return BelongsTo<ArticleCommentThread, $this>
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(ArticleCommentThread::class, 'thread_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

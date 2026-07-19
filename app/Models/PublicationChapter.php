<?php

namespace App\Models;

use Database\Factories\PublicationChapterFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $publication_issue_id
 * @property string $title
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read PublicationIssue $publicationIssue
 * @property-read Collection<int, Article> $articles
 */
class PublicationChapter extends Model
{
    /** @use HasFactory<PublicationChapterFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'publication_issue_id',
        'title',
        'position',
    ];

    /**
     * @return BelongsTo<PublicationIssue, $this>
     */
    public function publicationIssue(): BelongsTo
    {
        return $this->belongsTo(PublicationIssue::class);
    }

    /**
     * @return HasMany<Article, $this>
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class)
            ->orderBy('position')
            ->orderBy('id');
    }
}

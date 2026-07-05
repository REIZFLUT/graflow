<?php

namespace App\Models;

use Database\Factories\PublicationIssueFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $publication_id
 * @property string $label
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Publication $publication
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Article> $articles
 */
class PublicationIssue extends Model
{
    /** @use HasFactory<PublicationIssueFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'publication_id',
        'label',
    ];

    /**
     * @return BelongsTo<Publication, $this>
     */
    public function publication(): BelongsTo
    {
        return $this->belongsTo(Publication::class);
    }

    /**
     * @return HasMany<Article, $this>
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }
}

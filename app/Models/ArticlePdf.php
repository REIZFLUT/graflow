<?php

namespace App\Models;

use App\Enums\ArticlePdfKind;
use Database\Factories\ArticlePdfFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property int $article_id
 * @property int $owner_id
 * @property string $file_path
 * @property ArticlePdfKind $kind
 * @property string|null $parent_pdf_id
 * @property int|null $article_version_number
 * @property string $title
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Article $article
 * @property-read User $owner
 * @property-read ArticlePdf|null $parentPdf
 */
class ArticlePdf extends Model
{
    /** @use HasFactory<ArticlePdfFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'article_id',
        'owner_id',
        'file_path',
        'kind',
        'parent_pdf_id',
        'article_version_number',
        'title',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => ArticlePdfKind::class,
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
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return BelongsTo<ArticlePdf, $this>
     */
    public function parentPdf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_pdf_id');
    }
}

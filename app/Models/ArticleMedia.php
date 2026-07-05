<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property int|null $article_id
 * @property int $owner_id
 * @property string|null $staging_token
 * @property string $original_path
 * @property string $preview_webp_path
 * @property string $preview_jpeg_path
 * @property string $original_filename
 * @property string $mime_type
 * @property int $width
 * @property int $height
 * @property int $file_size
 * @property string $alt_text
 * @property string $copyright
 * @property string|null $caption
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Article|null $article
 * @property-read User $owner
 */
class ArticleMedia extends Model
{
    use HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'article_id',
        'owner_id',
        'staging_token',
        'original_path',
        'preview_webp_path',
        'preview_jpeg_path',
        'original_filename',
        'mime_type',
        'width',
        'height',
        'file_size',
        'alt_text',
        'copyright',
        'caption',
    ];

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

    public function isStaging(): bool
    {
        return $this->article_id === null;
    }
}

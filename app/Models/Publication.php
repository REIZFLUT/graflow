<?php

namespace App\Models;

use App\Enums\PublicationEditorFont;
use Database\Factories\PublicationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property int|null $editor_settings_set_id
 * @property int $owner_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $owner
 * @property-read EditorSettingsSet|null $editorSettingsSet
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PublicationIssue> $issues
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PublicationCategory> $categories
 */
class Publication extends Model
{
    /** @use HasFactory<PublicationFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'editor_settings_set_id',
        'owner_id',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return BelongsTo<EditorSettingsSet, $this>
     */
    public function editorSettingsSet(): BelongsTo
    {
        return $this->belongsTo(EditorSettingsSet::class);
    }

    /**
     * @return HasMany<PublicationIssue, $this>
     */
    public function issues(): HasMany
    {
        return $this->hasMany(PublicationIssue::class);
    }

    /**
     * @return HasMany<PublicationCategory, $this>
     */
    public function categories(): HasMany
    {
        return $this->hasMany(PublicationCategory::class);
    }
}

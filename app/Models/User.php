<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property UserRole $role
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Article> $articles
 * @property-read Collection<int, Article> $authoredArticles
 * @property-read Collection<int, Article> $managedArticles
 * @property-read Collection<int, Article> $assignedArticles
 * @property-read Collection<int, ArticleParticipant> $articleParticipations
 * @property-read Collection<int, Article> $participatingArticles
 * @property-read Collection<int, ArticleWorkflowEvent> $workflowEvents
 * @property-read Collection<int, ArticleWorkflowEvent> $assignedWorkflowEvents
 */
#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function canManageEditorSettingsSets(): bool
    {
        return $this->role->canManageEditorSettingsSets();
    }

    public function canManageUsers(): bool
    {
        return $this->role->canManageUsers();
    }

    public function hasBlockingRelationships(): bool
    {
        return $this->articles()->exists()
            || $this->publications()->exists()
            || $this->workflowEvents()->exists()
            || ArticleVersion::query()->where('created_by_id', $this->id)->exists();
    }

    /**
     * @return HasMany<Article, $this>
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'owner_id');
    }

    /**
     * @return HasMany<Article, $this>
     */
    public function authoredArticles(): HasMany
    {
        return $this->hasMany(Article::class, 'author_id');
    }

    /**
     * @return HasMany<Article, $this>
     */
    public function managedArticles(): HasMany
    {
        return $this->hasMany(Article::class, 'product_manager_id');
    }

    /**
     * @return HasMany<Article, $this>
     */
    public function assignedArticles(): HasMany
    {
        return $this->hasMany(Article::class, 'current_assignee_id');
    }

    /**
     * @return HasMany<ArticleParticipant, $this>
     */
    public function articleParticipations(): HasMany
    {
        return $this->hasMany(ArticleParticipant::class);
    }

    /**
     * @return BelongsToMany<Article, $this>
     */
    public function participatingArticles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'article_participants')
            ->withPivot('process_role')
            ->withTimestamps();
    }

    /**
     * @return HasMany<ArticleWorkflowEvent, $this>
     */
    public function workflowEvents(): HasMany
    {
        return $this->hasMany(ArticleWorkflowEvent::class, 'actor_id');
    }

    /**
     * @return HasMany<ArticleWorkflowEvent, $this>
     */
    public function assignedWorkflowEvents(): HasMany
    {
        return $this->hasMany(ArticleWorkflowEvent::class, 'assignee_id');
    }

    /**
     * @return HasMany<Publication, $this>
     */
    public function publications(): HasMany
    {
        return $this->hasMany(Publication::class, 'owner_id');
    }
}

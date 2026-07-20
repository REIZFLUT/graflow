<?php

namespace App\Enums;

enum NotificationType: string
{
    case AssignedResponsible = 'assigned_responsible';
    case ArticlePublished = 'article_published';
    case ManuscriptSubmitted = 'manuscript_submitted';
    case RevisionRequested = 'revision_requested';
    case EditorialCompleted = 'editorial_completed';
    case ReadyForPublication = 'ready_for_publication';

    /**
     * Roles for which this notification type is relevant and enabled by default.
     *
     * @return list<UserRole>
     */
    public function relevantRoles(): array
    {
        return match ($this) {
            self::AssignedResponsible => [
                UserRole::Author,
                UserRole::Editor,
                UserRole::Lector,
                UserRole::ProductManager,
            ],
            self::ArticlePublished => [
                UserRole::Author,
                UserRole::ProductManager,
            ],
            self::ManuscriptSubmitted,
            self::RevisionRequested,
            self::EditorialCompleted,
            self::ReadyForPublication => [
                UserRole::ProductManager,
            ],
        };
    }

    public function isRelevantForRole(UserRole $role): bool
    {
        return in_array($role, $this->relevantRoles(), true);
    }

    /**
     * Notification types relevant for the given role.
     *
     * @return list<self>
     */
    public static function forRole(UserRole $role): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $type): bool => $type->isRelevantForRole($role),
        ));
    }
}

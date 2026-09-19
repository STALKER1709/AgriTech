<?php

declare(strict_types=1);

namespace App\Enums;

enum PublicationStatus: string
{
    case Draft = 'draft';
    case InReview = 'in_review';
    case Published = 'published';
    case Rejected = 'rejected';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::InReview => 'En cours de modération',
            self::Published => 'Publié',
            self::Rejected => 'Refusé',
            self::Archived => 'Archivé',
        };
    }

    /**
     * Business rule RG09: a publication reaches Published through InReview,
     * unless prior moderation is switched off in the settings.
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::InReview, self::Published, self::Archived],
            self::InReview => [self::Published, self::Rejected, self::Archived],
            self::Published => [self::Archived, self::InReview],
            self::Rejected => [self::Draft, self::InReview, self::Archived],
            self::Archived => [self::Draft],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), strict: true);
    }

    public function isVisibleToPublic(): bool
    {
        return $this === self::Published;
    }
}

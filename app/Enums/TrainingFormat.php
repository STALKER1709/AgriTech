<?php

declare(strict_types=1);

namespace App\Enums;

enum TrainingFormat: string
{
    case Video = 'video';
    case Pdf = 'pdf';
    case Mixed = 'mixed';

    public function label(): string
    {
        return match ($this) {
            self::Video => 'Vidéo',
            self::Pdf => 'Document PDF',
            self::Mixed => 'Vidéo et document',
        };
    }
}

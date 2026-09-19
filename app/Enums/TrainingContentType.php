<?php

declare(strict_types=1);

namespace App\Enums;

enum TrainingContentType: string
{
    case Video = 'video';
    case Pdf = 'pdf';

    public function label(): string
    {
        return match ($this) {
            self::Video => 'Vidéo',
            self::Pdf => 'Document PDF',
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Enums;

enum SettingType: string
{
    case Integer = 'integer';
    case Boolean = 'boolean';
    case String = 'string';

    public function label(): string
    {
        return match ($this) {
            self::Integer => 'Nombre entier',
            self::Boolean => 'Oui / Non',
            self::String => 'Texte',
        };
    }
}

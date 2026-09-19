<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case Client = 'client';
    case Farmer = 'farmer';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Client => 'Client',
            self::Farmer => 'Agriculteur',
            self::Admin => 'Administrateur',
        };
    }
}

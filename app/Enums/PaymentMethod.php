<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentMethod: string
{
    case MtnMomo = 'mtn_momo';
    case OrangeMoney = 'orange_money';

    public function label(): string
    {
        return match ($this) {
            self::MtnMomo => 'MTN Mobile Money',
            self::OrangeMoney => 'Orange Money',
        };
    }
}

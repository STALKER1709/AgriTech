<?php

declare(strict_types=1);

namespace App\Enums;

enum ProductUnit: string
{
    case Kilogram = 'kg';
    case Bag = 'sac';
    case Bunch = 'regime';
    case Litre = 'litre';
    case Piece = 'piece';
    case Tonne = 'tonne';

    public function label(): string
    {
        return match ($this) {
            self::Kilogram => 'Kilogramme',
            self::Bag => 'Sac',
            self::Bunch => 'Régime',
            self::Litre => 'Litre',
            self::Piece => 'Pièce',
            self::Tonne => 'Tonne',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Kilogram => 'kg',
            self::Bag => 'sac',
            self::Bunch => 'régime',
            self::Litre => 'L',
            self::Piece => 'pièce',
            self::Tonne => 't',
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Enums;

use App\Support\Quantity;

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

    /**
     * The unit as it reads after a quantity: "120 sacs", "12,5 kg".
     *
     * Symbols never take an s; words do. Writing "120 régime disponibles"
     * on a product page is the sort of detail a buyer notices.
     */
    public function countLabel(Quantity $quantity): string
    {
        $plural = match ($this) {
            self::Kilogram, self::Litre, self::Tonne => false,
            default => true,
        };

        $label = $this->shortLabel();

        return $plural && $quantity->isGreaterThan(Quantity::fromInteger(1))
            ? $label.'s'
            : $label;
    }
}

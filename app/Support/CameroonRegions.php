<?php

declare(strict_types=1);

namespace App\Support;

/**
 * The ten regions of Cameroon, offered as a closed list at farmer sign-up.
 *
 * A free-text field here would make the regional filters of the catalogue
 * useless, since the same region would be spelled several ways.
 */
final class CameroonRegions
{
    /**
     * @var array<int, string>
     */
    private const array REGIONS = [
        'Adamaoua',
        'Centre',
        'Est',
        'Extrême-Nord',
        'Littoral',
        'Nord',
        'Nord-Ouest',
        'Ouest',
        'Sud',
        'Sud-Ouest',
    ];

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return self::REGIONS;
    }

    public static function isValid(string $region): bool
    {
        return in_array($region, self::REGIONS, strict: true);
    }
}

<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * The provenance of the demonstration photographs.
 *
 * The photographs come from free image repositories; those under CC BY carry
 * an attribution obligation, which the credits page discharges. The manifest
 * sits next to the files it describes, in database/seeders/photos, so a file
 * and its licence can never drift apart.
 */
final class PhotoCredits
{
    public const string MANIFEST = 'seeders/photos/credits.json';

    /**
     * @return Collection<int|string, Collection<int, array{kind: string, file: string, title: string, author: string, licence: string, source: string}>>
     */
    public static function byKind(): Collection
    {
        return self::all()->groupBy('kind');
    }

    /**
     * @return Collection<int, array{kind: string, file: string, title: string, author: string, licence: string, source: string}>
     */
    public static function all(): Collection
    {
        $path = database_path(self::MANIFEST);

        if (! is_file($path)) {
            return collect();
        }

        /** @var mixed $decoded */
        $decoded = json_decode((string) file_get_contents($path), true);

        if (! is_array($decoded)) {
            return collect();
        }

        /** @var Collection<int, array{kind: string, file: string, title: string, author: string, licence: string, source: string}> */
        return collect($decoded);
    }

    /**
     * The human label of a manifest section.
     */
    public static function label(string $kind): string
    {
        return match ($kind) {
            'products' => __('Photos de produits'),
            'trainings' => __('Visuels de formations'),
            'app' => __('Visuels de l\'application'),
            default => $kind,
        };
    }
}

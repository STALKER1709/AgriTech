<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Produces a slug that is actually free.
 *
 * Two farmers both selling "Tomate fraîche" is the normal case, not the edge
 * case, and products.slug is unique. Appending a counter until the column
 * accepts it keeps both listings alive instead of failing the second one.
 */
final class SlugGenerator
{
    /**
     * @param  class-string<Model>  $model
     * @param  int|null  $ignoreId  the row being updated, which may legitimately
     *                              already hold the slug being asked for
     */
    public static function for(string $model, string $source, string $column = 'slug', ?int $ignoreId = null): string
    {
        $base = Str::slug($source);

        if ($base === '') {
            $base = Str::lower(Str::random(8));
        }

        $candidate = $base;
        $suffix = 1;

        while (self::taken($model, $column, $candidate, $ignoreId)) {
            $suffix++;
            $candidate = $base.'-'.$suffix;
        }

        return $candidate;
    }

    /**
     * @param  class-string<Model>  $model
     */
    private static function taken(string $model, string $column, string $candidate, ?int $ignoreId): bool
    {
        /** @var Builder<Model> $query */
        $query = $model::query()->where($column, $candidate);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        return $query->exists();
    }
}

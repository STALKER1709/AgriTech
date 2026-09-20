<?php

declare(strict_types=1);

namespace App\Services\Trainings;

use App\Enums\PublicationStatus;
use App\Enums\TrainingFormat;
use App\Models\Training;
use App\Models\User;
use App\Support\Money;
use App\Support\SlugGenerator;
use Illuminate\Support\Facades\DB;

/**
 * Creates and updates a farmer's trainings.
 *
 * Same shape as ProductService, same rules: a new training starts as a draft,
 * the slug follows the title only when the title actually changed, and
 * archiving replaces deletion because purchases point at the training and an
 * access history that loses its subject is not a history.
 */
final class TrainingService
{
    /**
     * @param  array{
     *     title: string,
     *     description: string,
     *     price: int,
     *     format: string,
     *     included_in_subscription: bool,
     * }  $attributes
     */
    public function create(User $farmer, array $attributes): Training
    {
        return DB::transaction(fn (): Training => Training::create([
            'farmer_id' => $farmer->id,
            'title' => $attributes['title'],
            'slug' => SlugGenerator::for(Training::class, $attributes['title']),
            'description' => $attributes['description'],
            'price' => Money::fromInteger($attributes['price']),
            'format' => TrainingFormat::from($attributes['format']),
            'included_in_subscription' => $attributes['included_in_subscription'],
            'status' => PublicationStatus::Draft,
        ]));
    }

    /**
     * @param  array{
     *     title: string,
     *     description: string,
     *     price: int,
     *     format: string,
     *     included_in_subscription: bool,
     * }  $attributes
     */
    public function update(Training $training, array $attributes): Training
    {
        return DB::transaction(function () use ($training, $attributes): Training {
            // Same reasoning as Product: a stable URL for an unchanged title.
            $slug = $training->title === $attributes['title']
                ? $training->slug
                : SlugGenerator::for(Training::class, $attributes['title'], ignoreId: $training->id);

            $training->forceFill([
                'title' => $attributes['title'],
                'slug' => $slug,
                'description' => $attributes['description'],
                'price' => Money::fromInteger($attributes['price'])->amount,
                'format' => TrainingFormat::from($attributes['format'])->value,
                'included_in_subscription' => $attributes['included_in_subscription'],
            ])->save();

            return $training->refresh();
        });
    }

    public function archive(Training $training): void
    {
        DB::transaction(function () use ($training): void {
            $training->archive();
        });
    }
}

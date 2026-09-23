<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\PublicationStatus;
use App\Enums\TrainingFormat;
use App\Enums\UserStatus;
use App\Exceptions\InvalidStatusTransition;
use App\Support\Money;
use Database\Factories\TrainingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $farmer_id
 * @property string $title
 * @property string $slug
 * @property string $description
 * @property Money $price
 * @property TrainingFormat $format
 * @property bool $included_in_subscription
 * @property PublicationStatus $status
 * @property string|null $rejection_reason
 * @property-read int|null $contents_count comptage `withCount('contents')`, absent sans lui
 */
#[Fillable(['farmer_id', 'title', 'slug', 'description', 'price', 'format', 'included_in_subscription', 'status', 'rejection_reason'])]
class Training extends Model
{
    /** @use HasFactory<TrainingFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => MoneyCast::class,
            'format' => TrainingFormat::class,
            'included_in_subscription' => 'boolean',
            'status' => PublicationStatus::class,
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function farmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'farmer_id');
    }

    /** @return HasMany<TrainingContent, $this> */
    public function contents(): HasMany
    {
        return $this->hasMany(TrainingContent::class)->orderBy('position');
    }

    /** @return HasMany<TrainingPurchase, $this> */
    public function purchases(): HasMany
    {
        return $this->hasMany(TrainingPurchase::class);
    }

    /** @return MorphMany<Payment, $this> */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Where a training's cover lives, if it has one.
     *
     * A photograph is stored as .jpg, a drawn fallback as .png; the file is
     * keyed on the slug either way, so there is no column to keep in step.
     */
    public function coverPath(): ?string
    {
        $disk = Storage::disk((string) config('catalog.images.disk', 'local'));

        foreach (['jpg', 'png'] as $extension) {
            $path = 'training-covers/'.$this->slug.'.'.$extension;

            if ($disk->exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Whether a cover file exists on the private disk. Views use this to fall
     * back to the gradient hero when neither a photograph nor a drawing is
     * there (for instance on a machine without GD).
     */
    public function hasCover(): bool
    {
        return $this->coverPath() !== null;
    }

    /**
     * The public URL of the cover, served through the controller like product
     * images are. Only meaningful when hasCover() is true.
     */
    public function coverUrl(): string
    {
        return route('trainings.cover', ['training' => $this->slug]);
    }

    /**
     * Business rule RG05: content is reachable either through a paid purchase
     * or through an active subscription, when the training is part of it.
     */
    public function isAccessibleBy(User $client): bool
    {
        $hasPurchased = $this->purchases()
            ->where('client_id', $client->id)
            ->exists();

        if ($hasPurchased) {
            return true;
        }

        return $this->included_in_subscription && $client->hasActiveSubscription();
    }

    /*
    |--------------------------------------------------------------------------
    | Status transitions
    |--------------------------------------------------------------------------
    */

    public function transitionTo(PublicationStatus $target): void
    {
        if (! $this->status->canTransitionTo($target)) {
            throw InvalidStatusTransition::between('training', $this->status, $target);
        }

        $this->status = $target;
        $this->save();
    }

    public function submitForReview(): void
    {
        $this->transitionTo(PublicationStatus::InReview);
    }

    public function publish(): void
    {
        $this->transitionTo(PublicationStatus::Published);
    }

    public function rejectPublication(): void
    {
        $this->transitionTo(PublicationStatus::Rejected);
    }

    public function archive(): void
    {
        $this->transitionTo(PublicationStatus::Archived);
    }

    /** @param  Builder<$this>  $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', PublicationStatus::Published);
    }

    /**
     * Same reasoning as Product: a suspended farmer stops selling.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeVisibleToPublic(Builder $query): void
    {
        $query->where('status', PublicationStatus::Published)
            ->whereHas('farmer', fn ($farmer) => $farmer->where('status', UserStatus::Active));
    }

    /** @param  Builder<$this>  $query */
    public function scopeIncludedInSubscription(Builder $query): void
    {
        $query->where('included_in_subscription', true);
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SubscriptionStatus;
use App\Exceptions\InvalidStatusTransition;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\SubscriptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $client_id
 * @property int $plan_id
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property SubscriptionStatus $status
 */
#[Fillable(['client_id', 'plan_id', 'starts_at', 'ends_at', 'status'])]
class Subscription extends Model
{
    /** @use HasFactory<SubscriptionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => SubscriptionStatus::class,
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /** @return BelongsTo<SubscriptionPlan, $this> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    /** @return MorphMany<Payment, $this> */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    /**
     * Business rule RG05: only a live subscription opens the trainings it
     * includes. A status of Active alone is not enough — the term must still
     * be running, since the expiry sweep may not have passed yet.
     */
    public function isCurrentlyActive(): bool
    {
        return $this->status->grantsAccess()
            && $this->ends_at !== null
            && $this->ends_at->isFuture();
    }

    public function hasLapsed(): bool
    {
        return $this->ends_at !== null && $this->ends_at->isPast();
    }

    /*
    |--------------------------------------------------------------------------
    | Status transitions
    |--------------------------------------------------------------------------
    */

    public function transitionTo(SubscriptionStatus $target): void
    {
        if (! $this->status->canTransitionTo($target)) {
            throw InvalidStatusTransition::between('subscription', $this->status, $target);
        }

        $this->status = $target;
        $this->save();
    }

    /**
     * Start the term. The clock only starts once the payment is confirmed, so
     * a client never loses days waiting for the gateway.
     */
    public function activate(?CarbonInterface $startingAt = null): void
    {
        // Normalised to an immutable instance so that deriving the end date
        // cannot shift the start date underneath us.
        $startsAt = CarbonImmutable::instance($startingAt ?? now());

        $this->transitionTo(SubscriptionStatus::Active);

        $this->forceFill([
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->addDays($this->plan->duration_days),
        ])->save();
    }

    public function expire(): void
    {
        $this->transitionTo(SubscriptionStatus::Expired);
    }

    public function cancel(): void
    {
        $this->transitionTo(SubscriptionStatus::Cancelled);
    }

    /** @param  Builder<$this>  $query */
    public function scopeLapsed(Builder $query): void
    {
        $query->where('status', SubscriptionStatus::Active)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now());
    }
}

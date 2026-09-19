<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\PaymentMethod;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Exceptions\InvalidStatusTransition;
use App\Support\Money;
use Carbon\CarbonInterface;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property Money $amount
 * @property string $currency
 * @property PaymentPurpose $purpose
 * @property string|null $payable_type
 * @property int|null $payable_id
 * @property string $provider
 * @property string $provider_reference
 * @property PaymentMethod $method
 * @property PaymentStatus $status
 * @property array<string, mixed>|null $raw_payload
 * @property string $idempotency_key
 * @property Carbon|null $confirmed_at
 * @property Carbon|null $created_at
 */
#[Fillable([
    'user_id', 'amount', 'currency', 'purpose', 'payable_type', 'payable_id',
    'provider', 'provider_reference', 'method', 'status', 'raw_payload',
    'idempotency_key', 'confirmed_at',
])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => MoneyCast::class,
            'purpose' => PaymentPurpose::class,
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'raw_payload' => 'array',
            'confirmed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * What this payment pays for: an order, a training, a subscription, or
     * the farmer registration fee.
     *
     * @return MorphTo<Model, $this>
     */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getRouteKeyName(): string
    {
        return 'provider_reference';
    }

    public function isSuccessful(): bool
    {
        return $this->status === PaymentStatus::Succeeded;
    }

    /*
    |--------------------------------------------------------------------------
    | Status transitions
    |--------------------------------------------------------------------------
    */

    public function transitionTo(PaymentStatus $target): void
    {
        if (! $this->status->canTransitionTo($target)) {
            throw InvalidStatusTransition::between('payment', $this->status, $target);
        }

        $this->status = $target;
        $this->save();
    }

    /**
     * Business rule RG06: a payment only reaches Succeeded on a signed
     * callback verified server-side, or on a status read from the gateway.
     * A browser redirect is never enough, and the simulated gateway is held
     * to exactly the same standard.
     */
    public function markAsSucceeded(?CarbonInterface $confirmedAt = null): void
    {
        $this->transitionTo(PaymentStatus::Succeeded);

        $this->forceFill(['confirmed_at' => $confirmedAt ?? now()])->save();
    }

    public function markAsPending(): void
    {
        $this->transitionTo(PaymentStatus::Pending);
    }

    public function markAsFailed(): void
    {
        $this->transitionTo(PaymentStatus::Failed);
    }

    public function markAsExpired(): void
    {
        $this->transitionTo(PaymentStatus::Expired);
    }

    public function markAsRefunded(): void
    {
        $this->transitionTo(PaymentStatus::Refunded);
    }

    /**
     * Payments the reconciliation task has to chase up with the gateway.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeAwaitingOutcome(Builder $query): void
    {
        $query->whereIn('status', [PaymentStatus::Initiated, PaymentStatus::Pending]);
    }

    /** @param  Builder<$this>  $query */
    public function scopeSucceeded(Builder $query): void
    {
        $query->where('status', PaymentStatus::Succeeded);
    }
}

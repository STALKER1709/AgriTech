<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PaymentCallbackFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One callback received from the gateway, kept verbatim.
 *
 * The unique event_id is what makes the webhook idempotent: a replayed
 * callback collides on insert and is answered without any second effect.
 *
 * @property int $id
 * @property int $payment_id
 * @property string $event_id
 * @property string $signature
 * @property array<string, mixed> $payload
 * @property Carbon $received_at
 * @property Carbon|null $processed_at
 */
#[Fillable(['payment_id', 'event_id', 'signature', 'payload', 'received_at', 'processed_at'])]
class PaymentCallback extends Model
{
    /** @use HasFactory<PaymentCallbackFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Payment, $this> */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function wasProcessed(): bool
    {
        return $this->processed_at !== null;
    }

    public function markProcessed(): void
    {
        $this->forceFill(['processed_at' => now()])->save();
    }
}

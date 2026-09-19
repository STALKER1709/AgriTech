<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\FarmerProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $farm_name
 * @property string $region
 * @property string $city
 * @property string|null $description
 * @property Carbon|null $validated_at
 * @property int|null $validated_by
 * @property string|null $rejection_reason
 */
#[Fillable(['user_id', 'farm_name', 'region', 'city', 'description'])]
class FarmerProfile extends Model
{
    /** @use HasFactory<FarmerProfileFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'validated_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function markValidatedBy(User $admin): void
    {
        $this->forceFill([
            'validated_at' => now(),
            'validated_by' => $admin->id,
            'rejection_reason' => null,
        ])->save();
    }

    public function markRejectedBy(User $admin, string $reason): void
    {
        $this->forceFill([
            'validated_at' => null,
            'validated_by' => $admin->id,
            'rejection_reason' => $reason,
        ])->save();
    }

    public function isValidated(): bool
    {
        return $this->validated_at !== null;
    }
}

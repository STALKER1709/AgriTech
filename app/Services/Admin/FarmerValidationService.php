<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\User;
use App\Notifications\FarmerApproved;
use App\Notifications\FarmerRejected;
use Illuminate\Support\Facades\DB;

/**
 * Approves or rejects the farmer accounts that paid and are waiting.
 *
 * The decision, its audit entry and the farmer's notification belong together:
 * an approval that left no trail, or a refusal nobody was told about, is worse
 * than no decision at all. Hence one transaction.
 */
final class FarmerValidationService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function approve(User $farmer, User $admin): void
    {
        DB::transaction(function () use ($farmer, $admin): void {
            $before = $this->snapshot($farmer);

            $farmer->approve($admin);

            $this->audit->record(
                action: AuditLogger::FARMER_APPROVED,
                auditable: $farmer,
                before: $before,
                after: $this->snapshot($farmer->refresh()),
                actor: $admin,
            );
        });

        $farmer->notify(new FarmerApproved);
    }

    public function reject(User $farmer, User $admin, string $reason): void
    {
        DB::transaction(function () use ($farmer, $admin, $reason): void {
            $before = $this->snapshot($farmer);

            $farmer->reject($admin, $reason);

            $this->audit->record(
                action: AuditLogger::FARMER_REJECTED,
                auditable: $farmer,
                before: $before,
                after: [...$this->snapshot($farmer->refresh()), 'rejection_reason' => $reason],
                actor: $admin,
            );
        });

        $farmer->notify(new FarmerRejected($reason));
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(User $farmer): array
    {
        $farmer->loadMissing('farmerProfile');

        return [
            'status' => $farmer->status->value,
            'validated_at' => $farmer->farmerProfile?->validated_at?->toIso8601String(),
            'rejection_reason' => $farmer->farmerProfile?->rejection_reason,
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Suspends, reinstates and deletes accounts, with the trail RG11 requires.
 */
final class UserModerationService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function suspend(User $target, User $admin): void
    {
        DB::transaction(function () use ($target, $admin): void {
            $before = ['status' => $target->status->value];

            $target->suspend();

            $this->audit->record(
                action: AuditLogger::USER_SUSPENDED,
                auditable: $target,
                before: $before,
                after: ['status' => $target->refresh()->status->value],
                actor: $admin,
            );
        });
    }

    public function reinstate(User $target, User $admin): void
    {
        DB::transaction(function () use ($target, $admin): void {
            $before = ['status' => $target->status->value];

            $target->reinstate();

            $this->audit->record(
                action: AuditLogger::USER_REINSTATED,
                auditable: $target,
                before: $before,
                after: ['status' => $target->refresh()->status->value],
                actor: $admin,
            );
        });
    }

    /**
     * Business rule RG08: the row stays, the person goes.
     *
     * What is recorded in the audit trail is deliberately not the personal
     * data being removed — writing the email into the log would defeat the
     * anonymisation it is recording. The account's id and role are enough to
     * follow the history.
     */
    public function delete(User $target, User $admin): void
    {
        DB::transaction(function () use ($target, $admin): void {
            $before = [
                'status' => $target->status->value,
                'role' => $target->role->value,
                'had_email' => $target->email !== null,
                'had_phone' => $target->phone !== null,
            ];

            $target->anonymise();

            $this->audit->record(
                action: AuditLogger::USER_DELETED,
                auditable: $target,
                before: $before,
                after: [
                    'status' => $target->refresh()->status->value,
                    'role' => $target->role->value,
                    'had_email' => $target->email !== null,
                    'had_phone' => $target->phone !== null,
                ],
                actor: $admin,
            );
        });
    }
}

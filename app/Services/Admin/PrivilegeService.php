<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Privilege;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Grants and revokes administrative privileges.
 */
final class PrivilegeService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Replace an administrator's privileges with exactly this set of codes.
     *
     * @param  array<int, string>  $codes
     */
    public function sync(User $target, array $codes, User $admin): void
    {
        DB::transaction(function () use ($target, $codes, $admin): void {
            $before = $this->codesOf($target);

            $ids = Privilege::query()
                ->whereIn('code', $codes)
                ->pluck('id');

            $target->privileges()->sync($ids);

            $after = $this->codesOf($target->load('privileges'));

            // Nothing changed, nothing worth an audit entry: a log full of
            // no-ops is a log nobody reads.
            if ($before === $after) {
                return;
            }

            $this->audit->record(
                action: AuditLogger::PRIVILEGES_UPDATED,
                auditable: $target,
                before: ['privileges' => $before],
                after: ['privileges' => $after],
                actor: $admin,
            );
        });
    }

    /**
     * @return array<int, string>
     */
    private function codesOf(User $user): array
    {
        $user->loadMissing('privileges');

        $codes = $user->privileges->pluck('code')->all();
        sort($codes);

        return $codes;
    }
}

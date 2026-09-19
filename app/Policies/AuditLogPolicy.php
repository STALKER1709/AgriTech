<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Privilege;
use App\Models\User;

final class AuditLogPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasPrivilege(Privilege::VIEW_AUDIT_LOG);
    }
}

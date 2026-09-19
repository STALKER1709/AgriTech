<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Privilege;
use App\Models\Setting;
use App\Models\User;

final class SettingPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasPrivilege(Privilege::MANAGE_SETTINGS);
    }

    public function update(User $actor, Setting $setting): bool
    {
        return $actor->hasPrivilege(Privilege::MANAGE_SETTINGS);
    }
}

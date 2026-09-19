<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Category;
use App\Models\Privilege;
use App\Models\User;

final class CategoryPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasPrivilege(Privilege::MANAGE_CATEGORIES);
    }

    public function create(User $actor): bool
    {
        return $actor->hasPrivilege(Privilege::MANAGE_CATEGORIES);
    }

    public function update(User $actor, Category $category): bool
    {
        return $actor->hasPrivilege(Privilege::MANAGE_CATEGORIES);
    }

    /**
     * A category holding products cannot go: the foreign key restricts it, and
     * the screen has to say so plainly rather than let a SQL error surface.
     */
    public function delete(User $actor, Category $category): bool
    {
        return $actor->hasPrivilege(Privilege::MANAGE_CATEGORIES)
            && $category->products()->doesntExist();
    }
}

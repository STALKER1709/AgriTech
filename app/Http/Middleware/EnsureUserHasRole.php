<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps each role inside its own area of the application.
 *
 * Hiding a link is never enough; this is the server-side check that decides.
 */
final class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(403);
        }

        $allowed = array_map(
            fn (string $role): UserRole => UserRole::from($role),
            $roles,
        );

        if (! in_array($user->role, $allowed, strict: true)) {
            abort(403, __('Vous n\'avez pas accès à cet espace.'));
        }

        return $next($request);
    }
}

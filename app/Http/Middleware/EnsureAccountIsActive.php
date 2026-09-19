<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the working areas of the application against accounts that are not
 * active yet.
 *
 * A farmer awaiting payment or validation can sign in and follow their file,
 * but must not reach the screens where they would publish or sell. That is
 * business rule RG01, enforced at the route rather than per screen.
 */
final class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(403);
        }

        if (! $user->isActive()) {
            return redirect()->route('account.status');
        }

        return $next($request);
    }
}

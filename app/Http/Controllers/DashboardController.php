<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Sends each signed-in user to their own area.
 *
 * Fortify redirects to /dashboard after login, and this is the one place that
 * decides where that actually leads. An account that is not active yet lands
 * on the status screen instead, since its area is closed to it.
 */
final class DashboardController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(403);
        }

        if (! $user->isActive()) {
            return redirect()->route('account.status');
        }

        return redirect()->route(match ($user->role) {
            UserRole::Client => 'client.dashboard',
            UserRole::Farmer => 'farmer.dashboard',
            UserRole::Admin => 'admin.dashboard',
        });
    }
}

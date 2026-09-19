<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Throttles the auth routes Fortify leaves unprotected.
 *
 * Fortify throttles login and email verification out of the box, but not
 * registration or password reset requests, both of which are worth abusing:
 * one to mass-create accounts, the other to spray reset links at addresses.
 *
 * Runs on every Fortify route and steps aside immediately for anything that
 * is not one of the submissions below.
 */
final class ThrottleSensitiveAuthRoutes
{
    /**
     * Attempts allowed per minute, per IP address, by route name.
     *
     * @var array<string, int>
     */
    private const array LIMITS = [
        'register' => 5,
        'register.store' => 5,
        'password.email' => 5,
        'password.update' => 5,
    ];

    private const int DECAY_SECONDS = 60;

    public function __construct(private readonly RateLimiter $limiter) {}

    public function handle(Request $request, Closure $next): Response
    {
        $name = $request->route()?->getName();

        if (! $request->isMethod('POST') || $name === null || ! isset(self::LIMITS[$name])) {
            return $next($request);
        }

        $key = 'auth-attempts|'.$name.'|'.$request->ip();
        $maxAttempts = self::LIMITS[$name];

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            throw ValidationException::withMessages([
                $this->fieldFor($name) => __('auth.throttle_attempts', [
                    'seconds' => $this->limiter->availableIn($key),
                ]),
            ]);
        }

        $this->limiter->hit($key, self::DECAY_SECONDS);

        return $next($request);
    }

    /**
     * The field the message is attached to, so it lands next to the form
     * rather than in a bare error bag.
     */
    private function fieldFor(string $routeName): string
    {
        return str_starts_with($routeName, 'password.') ? 'email' : 'first_name';
    }
}

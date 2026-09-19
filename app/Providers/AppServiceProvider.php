<?php

namespace App\Providers;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureRateLimiting();
    }

    /**
     * Throttle the payment screens.
     *
     * Starting a payment writes a row and queues work, so it is worth a limit;
     * the pending screen polls every two seconds, so its limit has to leave
     * room for that.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('payments', function (Request $request): Limit {
            $user = $request->user();

            return Limit::perMinute(60)->by($user instanceof User ? (string) $user->id : (string) $request->ip());
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        // Surfaces N+1 queries while developing, by turning a silent lazy load
        // into an exception. Deliberately local-only: it must never take a
        // page down for a user.
        Model::preventLazyLoading(app()->environment('local'));

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}

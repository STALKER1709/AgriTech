<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Models\User;
use App\Services\Auth\AccountAccess;
use App\Services\Auth\UserLookup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
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
        // Login throttling is Fortify's own EnsureLoginIsNotThrottled action,
        // see the note on fortify.limiters.login. Registration and password
        // reset go through ThrottleSensitiveAuthRoutes.
        $this->configureActions();
        $this->configureAuthentication();
        $this->configureViews();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);
    }

    /**
     * Resolve the single login field to an account, then check that the
     * account may open a session at all.
     *
     * Returning null lets Fortify answer with the generic "these credentials
     * do not match" message, which is what a wrong password deserves. A
     * correct password on a suspended account gets a specific message
     * instead: sending that person back to the same form teaches them nothing.
     */
    private function configureAuthentication(): void
    {
        Fortify::authenticateUsing(function (Request $request): ?User {
            $login = $request->string(Fortify::username())->toString();
            $password = $request->string('password')->toString();

            $user = app(UserLookup::class)->findByLogin($login);

            if (! $user instanceof User || ! Hash::check($password, $user->password)) {
                return null;
            }

            $refusal = app(AccountAccess::class)->refusalReason($user);

            if ($refusal !== null) {
                throw ValidationException::withMessages([
                    Fortify::username() => __($refusal),
                ]);
            }

            return $user;
        });
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn () => view('livewire.auth.login'));
        Fortify::verifyEmailView(fn () => view('livewire.auth.verify-email'));
        Fortify::confirmPasswordView(fn () => view('livewire.auth.confirm-password'));
        Fortify::registerView(fn () => view('livewire.auth.register'));
        Fortify::resetPasswordView(fn () => view('livewire.auth.reset-password'));
        Fortify::requestPasswordResetLinkView(fn () => view('livewire.auth.forgot-password'));
    }
}

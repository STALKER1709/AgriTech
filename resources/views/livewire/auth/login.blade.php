<x-layouts::auth :title="__('Se connecter')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Connexion à votre compte')" :description="__('Saisissez votre adresse e-mail ou votre numéro de téléphone, puis votre mot de passe')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />


        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Identifiant : adresse e-mail ou numéro de téléphone -->
            <flux:input
                name="login"
                :label="__('Adresse e-mail ou téléphone')"
                :value="old('login')"
                type="text"
                required
                autofocus
                autocomplete="username"
                placeholder="vous@exemple.cm ou 650 00 00 01"
            />

            <!-- Password -->
            <div class="relative">
                <flux:input
                    name="password"
                    :label="__('Mot de passe')"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('Mot de passe')"
                    viewable
                />

                @if (Route::has('password.request'))
                    <flux:link class="absolute top-0 text-sm end-0" :href="route('password.request')" wire:navigate>
                        {{ __('Mot de passe oublié ?') }}
                    </flux:link>
                @endif
            </div>

            <!-- Remember Me -->
            <flux:checkbox name="remember" :label="__('Rester connecté')" :checked="old('remember')" />

            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                    {{ __('Se connecter') }}
                </flux:button>
            </div>
        </form>

        <div class="flex flex-col gap-1 text-sm text-center text-zinc-600 dark:text-zinc-400">
            <div class="space-x-1 rtl:space-x-reverse">
                <span>{{ __('Vous n\'avez pas de compte ?') }}</span>
                <flux:link :href="route('register')" wire:navigate>{{ __('Créer un compte client') }}</flux:link>
            </div>
            <div class="space-x-1 rtl:space-x-reverse">
                <span>{{ __('Vous êtes agriculteur ?') }}</span>
                <flux:link :href="route('register.farmer')" wire:navigate>{{ __('Créer un compte agriculteur') }}</flux:link>
            </div>
        </div>
    </div>
</x-layouts::auth>

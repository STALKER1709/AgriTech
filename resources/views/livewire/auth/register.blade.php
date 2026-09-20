<x-layouts::auth :title="__('Inscription')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Créer un compte client')" :description="__('Renseignez vos informations pour créer votre compte client')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf
            <!-- First name -->
            <flux:input
                name="first_name"
                :label="__('Prénom')"
                :value="old('first_name')"
                type="text"
                required
                autofocus
                autocomplete="given-name"
            />

            <!-- Last name -->
            <flux:input
                name="last_name"
                :label="__('Nom')"
                :value="old('last_name')"
                type="text"
                required
                autocomplete="family-name"
            />

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Adresse e-mail')"
                :value="old('email')"
                type="email"
                required
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Phone number -->
            <flux:input
                name="phone"
                :label="__('Numéro de téléphone')"
                :value="old('phone')"
                type="tel"
                required
                autocomplete="tel"
                placeholder="6XX XX XX XX"
            />

            <!-- Password -->
            <flux:input
                name="password"
                :label="__('Mot de passe')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Mot de passe')"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <!-- Confirm Password -->
            <flux:input
                name="password_confirmation"
                :label="__('Confirmer le mot de passe')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Confirmer le mot de passe')"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full" data-test="register-user-button">
                    {{ __('Créer mon compte') }}
                </flux:button>
            </div>
        </form>

        <div class="flex flex-col gap-1 text-sm text-center text-stitch-muted ">
            <div class="space-x-1 rtl:space-x-reverse">
                <span>{{ __('Vous avez déjà un compte ?') }}</span>
                <flux:link :href="route('login')" wire:navigate>{{ __('Se connecter') }}</flux:link>
            </div>
            <div class="space-x-1 rtl:space-x-reverse">
                <span>{{ __('Vous vendez des produits agricoles ?') }}</span>
                <flux:link :href="route('register.farmer')" wire:navigate>{{ __('Créer un compte agriculteur') }}</flux:link>
            </div>
        </div>
    </div>
</x-layouts::auth>

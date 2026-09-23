<x-layouts::auth :title="__('Réinitialiser le mot de passe')">
    <div class="flex flex-col gap-space-md">
        <x-auth-header icon="lock_reset" :title="__('Nouveau mot de passe')"
                       :description="__('Choisissez-en un que vous saurez retrouver.')" />

        <x-auth-session-status :status="session('status')" />

        <form method="POST" action="{{ route('password.update') }}"
              class="rounded-2xl bg-surface-container-lowest p-space-md shadow-raised flex flex-col gap-space-md">
            @csrf
            <input type="hidden" name="token" value="{{ request()->route('token') }}" />

            <x-form-field name="email" type="email" :label="__('Adresse e-mail')" icon="mail" required
                          :value="request('email')" autocomplete="email" placeholder="vous@exemple.cm" />

            <x-form-field name="password" type="password" :label="__('Nouveau mot de passe')" icon="lock"
                          required autocomplete="new-password" />

            <x-form-field name="password_confirmation" type="password" :label="__('Confirmer le mot de passe')"
                          icon="lock_reset" required autocomplete="new-password" />

            <button type="submit" data-test="reset-password-button"
                    class="h-14 w-full rounded-full bg-primary text-on-primary font-label-lg text-label-lg flex items-center justify-center gap-2 shadow-raised hover:bg-primary-container transition-colors">
                {{ __('Réinitialiser le mot de passe') }}
                <x-icon name="arrow_forward" size="20" />
            </button>
        </form>
    </div>
</x-layouts::auth>

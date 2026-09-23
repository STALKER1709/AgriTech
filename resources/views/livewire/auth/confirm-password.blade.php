<x-layouts::auth :title="__('Confirmer le mot de passe')">
    <div class="flex flex-col gap-space-md">
        <x-auth-header icon="shield" :title="__('Confirmer le mot de passe')"
                       :description="__('Cette section est protégée. Confirmez votre mot de passe pour continuer.')" />

        <x-auth-session-status :status="session('status')" />

        <form method="POST" action="{{ route('password.confirm.store') }}"
              class="rounded-2xl bg-surface-container-lowest p-space-md shadow-raised flex flex-col gap-space-md">
            @csrf

            <x-form-field name="password" type="password" :label="__('Mot de passe')" icon="lock"
                          required autocomplete="current-password" />

            <button type="submit" data-test="confirm-password-button"
                    class="h-14 w-full rounded-full bg-primary text-on-primary font-label-lg text-label-lg flex items-center justify-center gap-2 shadow-raised hover:bg-primary-container transition-colors">
                {{ __('Confirmer') }}
                <x-icon name="arrow_forward" size="20" />
            </button>
        </form>
    </div>
</x-layouts::auth>

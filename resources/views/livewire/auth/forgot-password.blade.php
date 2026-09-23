<x-layouts::auth :title="__('Mot de passe oublié')">
    <div class="flex flex-col gap-space-md">
        <x-auth-header icon="lock_reset" :title="__('Mot de passe oublié')"
                       :description="__('Saisissez votre adresse e-mail : un lien de réinitialisation vous y attendra.')" />

        <x-auth-session-status :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}"
              class="rounded-2xl bg-surface-container-lowest p-space-md shadow-raised flex flex-col gap-space-md">
            @csrf

            <x-auth-field name="email" type="email" :label="__('Adresse e-mail')" icon="mail" required
                          :value="old('email')" autocomplete="email" placeholder="vous@exemple.cm"
                          :hint="__('En local, le lien part dans le journal du mailer : storage/logs/laravel.log.')" />

            <button type="submit" data-test="email-password-reset-link-button"
                    class="h-14 w-full rounded-full bg-primary text-on-primary font-label-lg text-label-lg flex items-center justify-center gap-2 shadow-raised hover:bg-primary-container transition-colors">
                {{ __('Envoyer le lien de réinitialisation') }}
                <x-icon name="send" size="20" />
            </button>
        </form>

        <p class="text-center font-body-md text-body-md text-text-secondary">
            {{ __('Ou revenez à la') }}
            <a href="{{ route('login') }}" wire:navigate class="text-primary font-semibold hover:underline">
                {{ __('connexion') }}
            </a>
        </p>
    </div>
</x-layouts::auth>

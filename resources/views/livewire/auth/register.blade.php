{{--
    Inscription client. La maquette `agritech_inscription_agriculteur` est la
    seule des deux à être dessinée ; ce formulaire en reprend la grammaire —
    frise d'étapes, sections titrées, champs à icône — pour la version courte.

    Il n'y a pas d'étape « Adhésion » ici : un compte client est actif
    immédiatement, sans frais d'inscription.
--}}
<x-layouts::auth :title="__('Inscription')">
    <div class="flex flex-col gap-space-md">
        <div class="flex flex-col items-center text-center gap-space-xs">
            <a href="{{ route('register.choice') }}" wire:navigate
               class="self-start inline-flex items-center gap-1.5 text-primary font-label-lg text-label-lg py-1 hover:opacity-80 transition-opacity">
                <x-icon name="arrow_back" size="18" />
                {{ __('Changer de rôle') }}
            </a>

            <span class="flex w-16 h-16 items-center justify-center rounded-full bg-primary text-on-primary shadow-raised">
                <x-icon name="shopping_basket" size="30" />
            </span>
            <h1 class="font-headline-lg-mobile text-headline-lg-mobile text-text-primary tracking-tight">
                {{ __('Créer un compte client') }}
            </h1>
            <p class="font-body-md text-body-md text-text-secondary">
                {{ __('Votre compte est actif dès l\'inscription. Aucun frais.') }}
            </p>
        </div>

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}"
              class="rounded-2xl bg-surface-container-lowest p-space-md shadow-raised flex flex-col gap-space-md">
            @csrf

            <div class="flex items-center gap-space-xs">
                <x-icon name="person_check" size="20" class="text-primary" />
                <h2 class="font-headline-sm text-headline-sm text-text-primary">{{ __('Votre identité') }}</h2>
            </div>

            <div class="grid gap-space-md sm:grid-cols-2">
                <x-auth-field name="first_name" :label="__('Prénom')" icon="badge" required
                              :value="old('first_name')" autocomplete="given-name" placeholder="Clarisse" />

                <x-auth-field name="last_name" :label="__('Nom')" icon="badge" required
                              :value="old('last_name')" autocomplete="family-name" placeholder="Etoundi" />
            </div>

            <x-auth-field name="email" type="email" :label="__('Adresse e-mail')" icon="mail" required
                          :value="old('email')" autocomplete="email" placeholder="vous@exemple.cm" />

            <x-auth-field name="phone" type="tel" :label="__('Numéro de téléphone')" prefix="+237" required
                          :value="old('phone')" autocomplete="tel-national" placeholder="6XX XX XX XX"
                          :hint="__('Il sert aussi d\'identifiant de connexion.')" />

            <div class="flex items-center gap-space-xs pt-1">
                <x-icon name="lock" size="20" class="text-primary" />
                <h2 class="font-headline-sm text-headline-sm text-text-primary">{{ __('Votre mot de passe') }}</h2>
            </div>

            <x-auth-field name="password" type="password" :label="__('Mot de passe')" icon="lock" required
                          autocomplete="new-password" />

            <x-auth-field name="password_confirmation" type="password" :label="__('Confirmer le mot de passe')"
                          icon="lock_reset" required autocomplete="new-password" />

            <button type="submit" data-test="register-user-button"
                    class="h-14 w-full rounded-full bg-primary text-on-primary font-label-lg text-label-lg flex items-center justify-center gap-2 shadow-raised hover:bg-primary-container transition-colors">
                {{ __('Créer mon compte') }}
                <x-icon name="arrow_forward" size="20" />
            </button>
        </form>

        <p class="text-center font-body-md text-body-md text-text-secondary">
            {{ __('Vous avez déjà un compte ?') }}
            <a href="{{ route('login') }}" wire:navigate class="text-primary font-semibold hover:underline">
                {{ __('Se connecter') }}
            </a>
        </p>
    </div>
</x-layouts::auth>

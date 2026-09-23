{{--
    Reproduction de `agritech_inscription_agriculteur` : frise d'étapes en
    trois temps, sections « identité » puis « exploitation », champs à icône,
    sélecteur de région, et le rappel des frais d'inscription.

    Écarts : la maquette demande une superficie et des « filières » en
    pastilles, et coupe le formulaire en deux écrans enchaînés. Le schéma d'un
    profil d'exploitation ne porte ni surface ni filières ; la description
    libre, elle, existe et recueille ce que l'agriculteur veut en dire. Le
    formulaire reste d'un seul tenant : rien ne persiste entre deux étapes,
    et une étape 1 qui s'oublie au rafraîchissement serait pire qu'une page
    un peu longue. La frise dit donc où l'on en est dans le parcours complet,
    inscription puis adhésion.
--}}
{{-- Racine unique : le layout est appliqué par l'attribut #[Layout]
     du composant. L'envelopper ici lèverait une exception. --}}
<div class="flex flex-col gap-space-md">
    <a href="{{ route('register.choice') }}" wire:navigate
       class="self-start inline-flex items-center gap-1.5 text-primary font-label-lg text-label-lg py-1 hover:opacity-80 transition-opacity">
        <x-icon name="arrow_back" size="18" />
        {{ __('Changer de rôle') }}
    </a>

    {{-- Frise d'étapes --}}
    <div class="flex items-center justify-between gap-2 px-2">
        @foreach ([
            ['person_check', __('Identité'), 'current'],
            ['agriculture', __('Exploitation'), 'current'],
            ['workspace_premium', __('Adhésion'), 'todo'],
        ] as $index => [$icon, $label, $state])
            @if ($index > 0)
                <span @class([
                    'h-0.5 flex-1 rounded-full',
                    'bg-primary' => $state === 'current',
                    'bg-surface-container-high' => $state !== 'current',
                ])></span>
            @endif

            <div class="flex flex-col items-center gap-1 shrink-0">
                <span @class([
                    'w-10 h-10 rounded-full flex items-center justify-center shadow-card',
                    'bg-primary text-on-primary' => $state === 'current',
                    'bg-surface-container text-text-secondary' => $state === 'todo',
                ])>
                    <x-icon :name="$icon" size="20" />
                </span>
                <span @class([
                    'font-label-sm text-label-sm',
                    'text-primary font-semibold' => $state === 'current',
                    'text-text-secondary' => $state === 'todo',
                ])>{{ $label }}</span>
            </div>
        @endforeach
    </div>

    <div class="flex flex-col items-center text-center gap-space-xs">
        <h1 class="font-headline-lg-mobile text-headline-lg-mobile text-text-primary tracking-tight">
            {{ __('Créer un compte agriculteur') }}
        </h1>
        <p class="font-body-md text-body-md text-text-secondary max-w-md">
            {{ __('Votre compte n\'ouvre pas tout de suite : les frais d\'inscription réglés, un administrateur vérifie votre dossier.') }}
        </p>
    </div>

    <form wire:submit="register"
          class="rounded-2xl bg-surface-container-lowest p-space-md shadow-raised flex flex-col gap-space-md">

        <div class="flex items-center gap-space-xs">
            <x-icon name="person_check" size="20" class="text-primary" />
            <h2 class="font-headline-sm text-headline-sm text-text-primary">{{ __('Votre identité') }}</h2>
        </div>

        <div class="grid gap-space-md sm:grid-cols-2">
            <x-auth-field wire="first_name" :label="__('Prénom')" icon="badge" required
                          autocomplete="given-name" placeholder="Bernard" />

            <x-auth-field wire="last_name" :label="__('Nom')" icon="badge" required
                          autocomplete="family-name" placeholder="Awono" />
        </div>

        <div class="grid gap-space-md sm:grid-cols-2">
            <x-auth-field wire="email" type="email" :label="__('Adresse e-mail')" icon="mail" required
                          autocomplete="email" placeholder="vous@exemple.cm" />

            <x-auth-field wire="phone" type="tel" :label="__('Téléphone')" prefix="+237" required
                          autocomplete="tel-national" placeholder="6XX XX XX XX" />
        </div>

        <div class="flex items-center gap-space-xs pt-1">
            <x-icon name="agriculture" size="20" class="text-primary" />
            <h2 class="font-headline-sm text-headline-sm text-text-primary">{{ __('Votre exploitation') }}</h2>
        </div>

        <x-auth-field wire="farm_name" :label="__('Nom de l\'exploitation')" icon="store" required
                      :placeholder="__('Ex : Ferme du Mbam')" />

        <div class="grid gap-space-md sm:grid-cols-2">
            <x-auth-field wire="region" type="select" :label="__('Région')" icon="explore" required
                          :options="$this->regions()" :placeholder="__('Choisir une région')" />

            <x-auth-field wire="city" :label="__('Ville ou village')" icon="pin_drop" required
                          :placeholder="__('Ex : Obala')" />
        </div>

        <x-auth-field wire="description" type="textarea" :label="__('Votre exploitation en quelques lignes')"
                      icon="notes" :rows="4"
                      :placeholder="__('Vos cultures, vos saisons de récolte, vos méthodes…')" />

        <div class="flex items-center gap-space-xs pt-1">
            <x-icon name="lock" size="20" class="text-primary" />
            <h2 class="font-headline-sm text-headline-sm text-text-primary">{{ __('Votre mot de passe') }}</h2>
        </div>

        <div class="grid gap-space-md sm:grid-cols-2">
            <x-auth-field wire="password" type="password" :label="__('Mot de passe')" icon="lock" required
                          autocomplete="new-password" />

            <x-auth-field wire="password_confirmation" type="password" :label="__('Confirmer')"
                          icon="lock_reset" required autocomplete="new-password" />
        </div>

        {{-- Ce qui attend à l'étape suivante, dit avant de s'engager. --}}
        <div class="rounded-xl bg-surface-container-low p-space-sm flex items-start gap-space-sm">
            <span class="w-9 h-9 rounded-full bg-tertiary-fixed flex items-center justify-center shrink-0">
                <x-icon name="workspace_premium" size="18" class="text-on-tertiary-fixed-variant" />
            </span>
            <p class="font-label-sm text-label-sm text-text-secondary leading-relaxed">
                {{ __('À l\'étape suivante, les frais d\'inscription se règlent par Mobile Money simulé. Le compte passe en vérification une fois le paiement confirmé côté serveur, jamais avant.') }}
            </p>
        </div>

        <button type="submit" data-test="register-farmer-button" wire:loading.attr="disabled"
                class="h-14 w-full rounded-full bg-primary text-on-primary font-label-lg text-label-lg flex items-center justify-center gap-2 shadow-raised hover:bg-primary-container transition-colors disabled:opacity-60">
            {{ __('Continuer vers l\'adhésion') }}
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

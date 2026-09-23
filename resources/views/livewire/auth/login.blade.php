{{--
    Reproduction de `agritech_connexion` : motif de marque, carte centrée,
    bascule « Téléphone / Adresse e-mail », champs à icône, bouton de
    visibilité du mot de passe, « Se souvenir de moi », puis l'appel à
    l'inscription.

    Fortify authentifie sur un champ unique `login` qui accepte l'un ou
    l'autre (`Fortify::authenticateUsing`). Les deux onglets de la maquette ne
    changent donc pas la requête : ils décident seulement de ce que ce champ
    contient, et lequel des deux porte le `name`.

    Écarts : le code SMS instantané et « Continuer avec Google » supposent un
    opérateur et un fournisseur d'identité ; le projet tourne sans compte
    tiers ni connexion. Les indicateurs « Mode allégé » et « Chiffré » ne
    mesurent rien.
--}}
<x-layouts::auth :title="__('Se connecter')">
    <div class="flex flex-col gap-space-md"
         x-data="{ method: '{{ old('login') && ! str_contains((string) old('login'), '@') ? 'phone' : 'email' }}', show: false }">

        {{-- Motif de marque --}}
        <div class="flex flex-col items-center text-center gap-space-xs">
            <span class="flex w-16 h-16 items-center justify-center rounded-full bg-primary text-on-primary shadow-raised">
                <x-icon name="eco" size="32" filled />
            </span>
            <h1 class="font-headline-lg-mobile text-headline-lg-mobile text-text-primary tracking-tight">
                {{ __('Connexion à votre compte') }}
            </h1>
            <p class="font-body-md text-body-md text-text-secondary">
                {{ __('Le carrefour agricole du Cameroun') }}
            </p>
        </div>

        <x-auth-session-status class="text-center" :status="session('status')" />

        <div class="rounded-2xl bg-surface-container-lowest p-space-md shadow-raised flex flex-col gap-space-md">
            {{-- Bascule de méthode --}}
            <div class="flex p-1 rounded-full bg-surface-container" role="tablist">
                <button type="button" role="tab" x-on:click="method = 'phone'"
                        :aria-selected="method === 'phone'"
                        :class="method === 'phone' ? 'bg-primary text-on-primary shadow-card' : 'text-text-secondary hover:text-text-primary'"
                        class="flex-1 py-2 rounded-full font-label-lg text-label-lg flex items-center justify-center gap-1.5 transition-colors">
                    <x-icon name="phone_iphone" size="18" />
                    {{ __('Téléphone') }}
                </button>
                <button type="button" role="tab" x-on:click="method = 'email'"
                        :aria-selected="method === 'email'"
                        :class="method === 'email' ? 'bg-primary text-on-primary shadow-card' : 'text-text-secondary hover:text-text-primary'"
                        class="flex-1 py-2 rounded-full font-label-lg text-label-lg flex items-center justify-center gap-1.5 transition-colors">
                    <x-icon name="alternate_email" size="18" />
                    {{ __('Adresse e-mail') }}
                </button>
            </div>

            <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-space-md">
                @csrf

                {{-- Téléphone. Le badge « +237 » est verrouillé ; le champ ne
                     porte que la partie nationale, que `PhoneNumber` sait
                     lire telle quelle. --}}
                <div x-show="method === 'phone'" x-cloak class="flex flex-col gap-1.5">
                    <label for="login-phone" class="font-label-lg text-label-lg text-text-secondary">
                        {{ __('Numéro de téléphone') }}
                    </label>

                    <div class="flex items-center bg-surface-container-low rounded-xl px-space-sm py-1.5 focus-within:bg-surface-white focus-within:shadow-raised transition-all">
                        <span class="font-headline-sm text-headline-sm tracking-tight pr-3 pl-1 text-on-surface select-none">+237</span>
                        <span class="h-6 w-0.5 bg-outline-variant mr-3"></span>
                        <input id="login-phone" type="tel" inputmode="numeric" autocomplete="tel-national"
                               :name="method === 'phone' ? 'login' : ''"
                               :required="method === 'phone'"
                               value="{{ old('login') && ! str_contains((string) old('login'), '@') ? old('login') : '' }}"
                               placeholder="6XX XX XX XX"
                               class="w-full bg-transparent font-headline-sm text-headline-sm text-on-surface placeholder:text-outline outline-none tracking-wide" />
                    </div>
                </div>

                {{-- Adresse e-mail --}}
                <div x-show="method === 'email'" class="flex flex-col gap-1.5">
                    <label for="login-email" class="font-label-lg text-label-lg text-text-secondary">
                        {{ __('Adresse e-mail') }}
                    </label>

                    <div class="flex items-center bg-surface-container-low rounded-xl px-space-sm py-1.5 gap-2 focus-within:bg-surface-white focus-within:shadow-raised transition-all">
                        <x-icon name="mail" size="20" class="text-text-secondary shrink-0" />
                        <input id="login-email" type="email" autocomplete="username"
                               :name="method === 'email' ? 'login' : ''"
                               :required="method === 'email'"
                               value="{{ str_contains((string) old('login'), '@') ? old('login') : '' }}"
                               placeholder="vous@exemple.cm"
                               class="w-full bg-transparent font-body-md text-body-md text-on-surface placeholder:text-outline outline-none h-9" />
                    </div>
                </div>

                @error('login')
                    <p class="flex items-center gap-1 font-label-sm text-label-sm text-status-error -mt-2">
                        <x-icon name="error" size="14" />
                        {{ $message }}
                    </p>
                @enderror

                {{-- Mot de passe --}}
                <div class="flex flex-col gap-1.5">
                    <div class="flex items-center justify-between gap-space-sm">
                        <label for="login-password" class="font-label-lg text-label-lg text-text-secondary">
                            {{ __('Mot de passe') }}
                        </label>

                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" wire:navigate
                               class="font-label-sm text-label-sm text-primary font-semibold hover:underline">
                                {{ __('Mot de passe oublié ?') }}
                            </a>
                        @endif
                    </div>

                    <div class="flex items-center bg-surface-container-low rounded-xl px-space-sm py-1.5 gap-2 focus-within:bg-surface-white focus-within:shadow-raised transition-all">
                        <x-icon name="lock" size="20" class="text-text-secondary shrink-0" />
                        <input id="login-password" name="password" required autocomplete="current-password"
                               :type="show ? 'text' : 'password'" type="password"
                               placeholder="••••••••"
                               class="w-full bg-transparent font-body-md text-body-md text-on-surface placeholder:text-outline outline-none h-9" />
                        <button type="button" x-on:click="show = ! show"
                                :aria-label="show ? '{{ __('Masquer le mot de passe') }}' : '{{ __('Afficher le mot de passe') }}'"
                                class="shrink-0 text-text-secondary hover:text-text-primary transition-colors">
                            <x-icon name="visibility" size="20" x-show="! show" />
                            <x-icon name="visibility_off" size="20" x-show="show" x-cloak />
                        </button>
                    </div>

                    @error('password')
                        <p class="flex items-center gap-1 font-label-sm text-label-sm text-status-error">
                            <x-icon name="error" size="14" />
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Se souvenir de moi --}}
                <label class="flex items-center gap-2.5 cursor-pointer">
                    <input type="checkbox" name="remember" value="1" @checked(old('remember'))
                           class="w-5 h-5 rounded-md border-outline-variant text-primary focus:ring-primary" />
                    <span class="font-label-lg text-label-lg text-text-primary">{{ __('Rester connecté') }}</span>
                </label>

                <button type="submit" data-test="login-button"
                        class="h-14 w-full rounded-full bg-primary text-on-primary font-label-lg text-label-lg flex items-center justify-center gap-2 shadow-raised hover:bg-primary-container transition-colors">
                    {{ __('Se connecter') }}
                    <x-icon name="arrow_forward" size="20" />
                </button>
            </form>
        </div>

        {{-- Inscription --}}
        <div class="flex flex-col items-center gap-space-xs text-center">
            <p class="font-body-md text-body-md text-text-secondary">{{ __('Pas encore de compte ?') }}</p>

            <a href="{{ route('register.choice') }}" wire:navigate
               class="h-12 px-6 rounded-full bg-surface-container-lowest text-primary font-label-lg text-label-lg inline-flex items-center gap-1.5 shadow-card hover:bg-surface-container transition-colors">
                {{ __('S\'inscrire gratuitement') }}
                <x-icon name="arrow_outward" size="18" />
            </a>
        </div>
    </div>
</x-layouts::auth>

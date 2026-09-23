{{--
    Reproduction de `agritech_mon_compte` : carte de profil avec arc décoratif
    et pastille de vérification, grille de trois chiffres, puis deux sections
    de liens.

    Ce que la maquette propose et que la plateforme n'a pas disparaît :
    adresses de livraison, moyens de paiement enregistrés, mode économie de
    données, alertes SMS et WhatsApp, certificat, badges de fidélité. Les
    chiffres, eux, sont des comptages réels : chacun est celui que montre
    l'écran vers lequel il mène.
--}}
@php($user = $this->user())
@php($profile = $user->farmerProfile)
@php($place = collect([$profile?->city, $profile?->region])->filter()->join(', '))

<div class="flex w-full flex-col gap-space-md">
    {{-- Carte de profil --}}
    <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-primary via-[#165a31] to-[#0d3f20] text-on-primary p-space-md shadow-raised">
        <span class="absolute -right-10 -top-10 w-40 h-40 rounded-full bg-tertiary-fixed/10 pointer-events-none" aria-hidden="true"></span>

        <div class="relative z-10 flex items-center gap-space-md">
            <div class="relative shrink-0">
                <span class="w-16 h-16 rounded-full bg-surface flex items-center justify-center font-headline-md text-primary">
                    {{ $user->initials() }}
                </span>

                @if ($user->isActive())
                    <span class="absolute -bottom-0.5 -right-0.5 w-6 h-6 rounded-full bg-tertiary-fixed text-on-tertiary-fixed flex items-center justify-center ring-2 ring-primary"
                          title="{{ __('Compte actif') }}">
                        <x-icon name="verified" size="14" filled />
                    </span>
                @endif
            </div>

            <div class="min-w-0 flex-1">
                <h1 class="font-headline-md text-headline-md text-on-primary truncate">{{ $user->name }}</h1>

                <p class="font-label-sm text-label-sm text-on-primary/85 truncate">
                    {{ $profile?->farm_name ?? $user->email ?? $user->phone }}
                </p>

                @if ($place !== '')
                    <p class="font-label-sm text-label-sm text-on-primary/85 flex items-center gap-1 truncate mt-0.5">
                        <x-icon name="location_on" size="14" class="text-tertiary-fixed" />
                        {{ $place }}
                    </p>
                @endif
            </div>
        </div>

        <div class="relative z-10 flex flex-wrap items-center gap-2 mt-space-sm">
            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-on-primary/15 font-label-sm text-label-sm">
                <x-icon name="check_circle" size="14" class="text-tertiary-fixed" />
                {{ $user->role->label() }}
            </span>

            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-on-primary/15 font-label-sm text-label-sm">
                {{ $user->status->label() }}
            </span>

            @if ($user->created_at)
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-on-primary/15 font-label-sm text-label-sm">
                    <x-icon name="calendar_today" size="14" class="text-tertiary-fixed" />
                    {{ __('Depuis :date', ['date' => $user->created_at->timezone(config('app.timezone'))->translatedFormat('F Y')]) }}
                </span>
            @endif
        </div>

        <a href="{{ route('profile.edit') }}" wire:navigate
           class="relative z-10 mt-space-md h-11 px-5 rounded-full bg-secondary-fixed text-on-secondary-fixed-variant font-label-lg text-label-lg inline-flex items-center gap-2 shadow-card hover:bg-secondary-fixed-dim transition-colors">
            <x-icon name="edit" size="18" />
            {{ __('Modifier mon profil') }}
        </a>
    </section>

    {{-- Trois chiffres --}}
    <section class="grid grid-cols-3 gap-space-xs">
        @foreach ($this->stats() as $stat)
            <div class="rounded-xl bg-surface-container-lowest p-space-sm flex flex-col items-center text-center gap-1 shadow-card">
                <x-icon :name="$stat['icon']" size="20" class="text-primary" />
                <span class="font-headline-sm text-headline-sm text-text-primary truncate w-full">{{ $stat['value'] }}</span>
                <span class="font-label-sm text-label-sm text-text-secondary leading-tight">{{ $stat['label'] }}</span>
            </div>
        @endforeach
    </section>

    {{-- Sections de liens --}}
    @foreach ([
        [__('Mes activités'), $this->activities()],
        [__('Préférences et connexion'), $this->preferences()],
    ] as [$heading, $entries])
        <section class="flex flex-col gap-space-sm">
            <h2 class="font-label-lg text-label-lg text-text-secondary uppercase tracking-wide px-1">{{ $heading }}</h2>

            <div class="rounded-2xl bg-surface-container-lowest shadow-card overflow-hidden divide-y divide-surface-container">
                @foreach ($entries as $entry)
                    <a href="{{ $entry['href'] }}" wire:navigate
                       class="flex items-center gap-space-sm p-space-md hover:bg-surface-container-low transition-colors"
                       data-test="account-entry">
                        <span class="w-10 h-10 rounded-full bg-primary-fixed text-primary flex items-center justify-center shrink-0">
                            <x-icon :name="$entry['icon']" size="20" />
                        </span>

                        <span class="flex flex-col min-w-0 flex-1">
                            <span class="font-body-md-bold text-body-md-bold text-text-primary truncate">{{ $entry['title'] }}</span>
                            <span class="font-label-sm text-label-sm text-text-secondary truncate">{{ $entry['detail'] }}</span>
                        </span>

                        <x-icon name="chevron_right" size="20" class="text-text-secondary shrink-0" />
                    </a>
                @endforeach
            </div>
        </section>
    @endforeach

    {{-- Déconnexion --}}
    <form method="POST" action="{{ route('logout') }}" class="pb-space-sm">
        @csrf
        <button type="submit" data-test="logout"
                class="w-full h-12 rounded-full bg-surface-container-low text-status-error font-label-lg text-label-lg flex items-center justify-center gap-2 hover:bg-surface-container transition-colors">
            <x-icon name="logout" size="18" />
            {{ __('Se déconnecter') }}
        </button>
    </form>
</div>

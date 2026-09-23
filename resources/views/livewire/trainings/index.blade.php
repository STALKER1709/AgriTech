{{--
    Reproduction de `agritech_catalogue_formations` : chapeau d'écran, champ
    de recherche, pastilles de format comptées, bandeau d'abonnement et flux
    de cartes.

    Deux rangées de la maquette disparaissent. Les sous-chips de filière
    (« 🌽 Maïs & Céréales », « 🐔 Élevage ») supposent une taxonomie que le
    schéma des formations n'a pas. Et le bandeau annonce « 30 jours offerts » :
    aucun essai gratuit n'existe. Le nombre de formations et le prix du Pass,
    eux, sont lus en base.
--}}
<div class="flex w-full flex-col gap-space-md">
    {{-- Chapeau --}}
    <section class="flex flex-col">
        <div class="flex flex-wrap items-center gap-2 mb-1.5">
            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-primary-fixed text-primary font-label-sm text-label-sm">
                <x-icon name="school" size="14" />
                <span>{{ __('Académie AgriTech') }}</span>
            </span>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-surface-container text-text-secondary font-label-sm text-label-sm">
                {{ __('Cameroun') }}
            </span>
        </div>

        <h1 class="font-headline-lg-mobile text-headline-lg-mobile text-text-primary tracking-tight">
            {{ __('Formations agricoles & élevage') }}
        </h1>
        <p class="font-body-md text-body-md text-text-secondary mt-1.5">
            {{ __('Apprenez les meilleures techniques agronomiques auprès d\'experts camerounais.') }}
        </p>

        <div class="mt-space-md relative flex items-center bg-surface-container-lowest rounded-xl shadow-card">
            <span class="absolute left-3.5 text-text-secondary pointer-events-none">
                <x-icon name="search" size="22" />
            </span>
            <input type="search" wire:model.live.debounce.400ms="search"
                   placeholder="{{ __('Rechercher une formation, un formateur, une culture…') }}"
                   class="w-full h-[50px] pl-11 pr-11 bg-transparent rounded-xl font-body-md text-body-md text-text-primary placeholder:text-text-secondary/70 focus:outline-none focus:ring-2 focus:ring-primary" />

            @if ($this->activeFilterCount() > 0)
                <button type="button" wire:click="resetFilters"
                        aria-label="{{ __('Réinitialiser les filtres') }}"
                        class="absolute right-2.5 w-8 h-8 rounded-lg bg-surface-container flex items-center justify-center text-text-primary hover:bg-surface-container-high transition-colors">
                    <x-icon name="filter_alt_off" size="18" />
                </button>
            @endif
        </div>
    </section>

    {{-- Pastilles de format --}}
    @php($counts = $this->formatCounts())
    <div class="-mx-margin lg:mx-0 px-margin lg:px-0 w-auto overflow-x-auto no-scrollbar py-space-xs flex items-center gap-2">
        <button type="button" wire:click="$set('format', '')"
                @class([
                    'shrink-0 h-[38px] px-4 rounded-full font-label-lg text-label-lg flex items-center gap-1.5 shadow-card transition-colors',
                    'bg-primary text-on-primary' => $format === '',
                    'bg-surface-container-lowest text-text-secondary hover:text-text-primary' => $format !== '',
                ])>
            <span>{{ __('Toutes') }}</span>
            <span @class([
                'text-[11px] px-1.5 rounded-full font-bold',
                'bg-on-primary/20 text-on-primary' => $format === '',
                'bg-surface-container text-text-primary' => $format !== '',
            ])>{{ $counts['all'] }}</span>
        </button>

        {{-- Un format que personne ne vend n'a pas de pastille : un filtre qui
             ne peut rien renvoyer n'est pas un filtre, c'est un cul-de-sac. --}}
        @foreach ($this->formats() as $formatOption)
            @continue($counts[$formatOption->value] === 0 && $format !== $formatOption->value)
            @php($current = $format === $formatOption->value)
            <button type="button" wire:click="$set('format', '{{ $formatOption->value }}')"
                    @class([
                        'shrink-0 h-[38px] px-4 rounded-full font-label-lg text-label-lg flex items-center gap-1.5 shadow-card transition-colors',
                        'bg-primary text-on-primary' => $current,
                        'bg-surface-container-lowest text-text-secondary hover:text-text-primary' => ! $current,
                    ])>
                <x-icon :name="$formatOption === \App\Enums\TrainingFormat::Pdf ? 'description' : 'videocam'" size="18"
                        :class="$current ? '' : ($formatOption === \App\Enums\TrainingFormat::Pdf ? 'text-secondary' : 'text-primary')" />
                <span>{{ $formatOption->label() }} ({{ $counts[$formatOption->value] }})</span>
            </button>
        @endforeach

        <button type="button" wire:click="$toggle('includedOnly')"
                @class([
                    'shrink-0 h-[38px] px-4 rounded-full font-label-lg text-label-lg flex items-center gap-1.5 shadow-card transition-colors',
                    'bg-tertiary-fixed-dim text-on-tertiary-fixed' => $includedOnly,
                    'bg-tertiary-fixed text-on-tertiary-fixed hover:bg-tertiary-fixed-dim' => ! $includedOnly,
                ])>
            <x-icon name="auto_awesome" size="18" filled />
            <span>{{ __('Incluses abonnement') }}</span>
        </button>
    </div>

    {{-- Bandeau d'abonnement --}}
    @php($plan = $this->entryPlan())
    @if ($plan)
        <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-primary via-[#165a31] to-[#0d3f20] text-on-primary p-5 shadow-raised">
            <span class="absolute -right-8 -bottom-8 w-36 h-36 rounded-full bg-tertiary-fixed/15 blur-2xl pointer-events-none" aria-hidden="true"></span>
            <span class="absolute top-2 right-4 opacity-15 text-tertiary-fixed select-none pointer-events-none" aria-hidden="true">
                <x-icon name="workspace_premium" size="88" />
            </span>

            <div class="relative z-10">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-tertiary-fixed text-on-tertiary-fixed font-label-sm text-label-sm font-bold shadow-card mb-2.5">
                    <x-icon name="stars" size="15" filled />
                    <span>{{ __('Pass Formations') }}</span>
                </span>

                <h2 class="font-headline-md text-headline-md text-on-primary leading-snug">
                    {{ trans_choice(
                        ':count formation incluse dans le Pass|:count formations incluses dans le Pass',
                        $this->includedCount(),
                        ['count' => $this->includedCount()],
                    ) }}
                </h2>

                <p class="font-body-md text-body-md text-on-primary/90 mt-1 max-w-[280px]">
                    {{ __('À partir de') }}
                    <span class="font-bold text-tertiary-fixed">{{ $plan->price->format() }}</span>
                    {{ trans_choice('pour :count jour, sans reconduction automatique.|pour :count jours, sans reconduction automatique.', $plan->duration_days, ['count' => $plan->duration_days]) }}
                </p>

                <div class="flex flex-wrap items-center gap-3 mt-4">
                    <a href="{{ route('client.subscriptions') }}" wire:navigate
                       class="h-11 px-5 rounded-full bg-secondary-fixed text-on-secondary-fixed-variant font-label-lg text-label-lg inline-flex items-center justify-center gap-2 shadow-card hover:bg-secondary-fixed-dim transition-colors">
                        <span>{{ __('Découvrir l\'abonnement') }}</span>
                        <x-icon name="arrow_forward" size="18" />
                    </a>

                    @auth
                        <a href="{{ route('client.trainings') }}" wire:navigate
                           class="h-10 px-4 rounded-full border border-on-primary/40 font-label-lg text-label-lg inline-flex items-center gap-2 hover:bg-on-primary/10 transition-colors">
                            {{ __('Mes formations') }}
                        </a>
                    @endauth
                </div>
            </div>
        </section>
    @endif

    {{-- Flux --}}
    <section class="flex flex-col gap-space-md">
        <div class="flex items-center justify-between gap-space-sm">
            <h2 class="font-headline-sm text-headline-sm text-text-primary">{{ __('Formations disponibles') }}</h2>
            <span class="font-label-sm text-label-sm text-text-secondary shrink-0">
                {{ trans_choice(':count résultat|:count résultats', $trainings->total(), ['count' => $trainings->total()]) }}
            </span>
        </div>

        @if ($trainings->isEmpty())
            <div class="rounded-2xl bg-surface-container-lowest flex flex-col items-center gap-space-sm px-space-md py-space-2xl text-center shadow-card">
                <span class="flex w-14 h-14 items-center justify-center rounded-full bg-surface-container-low">
                    <x-icon name="school" size="28" class="text-text-secondary" />
                </span>
                <h3 class="font-headline-sm text-headline-sm text-text-primary">{{ __('Aucune formation ne correspond') }}</h3>
                <p class="font-body-md text-body-md text-text-secondary">
                    {{ __('Essayez d\'élargir votre recherche ou de retirer un filtre.') }}
                </p>

                @if ($this->activeFilterCount() > 0)
                    <button type="button" wire:click="resetFilters"
                            class="mt-1 h-12 px-6 rounded-full bg-surface-container-low text-primary font-label-lg text-label-lg inline-flex items-center gap-1.5 hover:bg-surface-container-high transition-colors">
                        <x-icon name="filter_alt_off" size="18" />
                        {{ __('Effacer les filtres') }}
                    </button>
                @endif
            </div>
        @else
            <div class="grid gap-gutter sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($trainings as $training)
                    <x-training-card :training="$training" />
                @endforeach
            </div>

            @if ($trainings->hasPages())
                <div class="pt-space-xs">{{ $trainings->links() }}</div>
            @endif
        @endif
    </section>
</div>

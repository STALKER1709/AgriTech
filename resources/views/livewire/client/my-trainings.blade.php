{{--
    Reproduction de `agritech_mes_formations` : bandeau d'abonnement, titre
    compté, carrousel de filtres et cartes de formation à grande vignette.

    La maquette montre une jauge d'avancement, un pourcentage, « Leçon
    suivante » et « Continuer le cours (Module 9) ». Rien n'enregistre où un
    client s'est arrêté : il n'existe pas de table de progression. Les
    emplacements gardent leur forme et disent ce qui est vrai — le nombre de
    modules, le premier d'entre eux, et d'où vient l'accès. Les filtres
    « En cours / Terminées / Hors-ligne » deviennent la seule distinction que
    le serveur connaisse : achetée, ou ouverte par le Pass.
--}}
<div class="flex w-full flex-col gap-space-md">
    {{-- Bandeau d'abonnement --}}
    @php($subscription = $this->subscription())
    @if ($subscription)
        <div class="bg-tertiary-fixed text-on-tertiary-fixed rounded-xl p-space-md shadow-card flex items-start gap-space-sm relative overflow-hidden">
            <span class="absolute -right-6 -bottom-6 w-24 h-24 rounded-full bg-tertiary-fixed-dim/40 pointer-events-none" aria-hidden="true"></span>

            <span class="w-10 h-10 rounded-full bg-surface/60 flex items-center justify-center shrink-0">
                <x-icon name="verified" size="24" class="text-on-tertiary-fixed-variant" />
            </span>

            <div class="flex-1 min-w-0 pr-space-xs z-10">
                <div class="flex items-center gap-space-xs mb-1">
                    <span class="font-label-lg text-label-lg text-on-tertiary-fixed tracking-tight">{{ __('Pass Formations actif') }}</span>
                    <span class="w-2 h-2 rounded-full bg-status-success inline-block"></span>
                </div>
                <p class="font-label-sm text-label-sm text-on-tertiary-fixed-variant leading-relaxed">
                    {{ __(':plan — valable jusqu\'au :date.', [
                        'plan' => $subscription->plan->name,
                        'date' => $subscription->ends_at?->timezone(config('app.timezone'))->translatedFormat('d F Y'),
                    ]) }}
                </p>
            </div>
        </div>
    @else
        <a href="{{ route('client.subscriptions') }}" wire:navigate
           class="bg-surface-container-low rounded-xl p-space-md shadow-card flex items-start gap-space-sm hover:bg-surface-container transition-colors">
            <span class="w-10 h-10 rounded-full bg-tertiary-fixed flex items-center justify-center shrink-0">
                <x-icon name="workspace_premium" size="24" class="text-on-tertiary-fixed-variant" />
            </span>

            <div class="flex-1 min-w-0">
                <span class="font-label-lg text-label-lg text-text-primary block">{{ __('Aucun Pass actif') }}</span>
                <p class="font-label-sm text-label-sm text-text-secondary leading-relaxed">
                    {{ __('Les formations incluses s\'ouvrent avec un abonnement en cours de validité.') }}
                </p>
            </div>

            <x-icon name="chevron_right" size="20" class="text-text-secondary shrink-0" />
        </a>
    @endif

    {{-- Titre et compteur --}}
    <div class="flex items-center justify-between gap-space-sm">
        <h1 class="font-headline-md text-headline-md text-text-primary tracking-tight">{{ __('Mes apprentissages') }}</h1>
        <span class="bg-surface-container-high text-primary font-label-sm text-label-sm px-3 py-1 rounded-full shadow-card shrink-0">
            {{ trans_choice(':count formation|:count formations', $this->trainings()->count(), ['count' => $this->trainings()->count()]) }}
        </span>
    </div>

    {{-- Filtres --}}
    <div class="-mx-margin lg:mx-0 px-margin lg:px-0 overflow-x-auto no-scrollbar flex items-center gap-space-xs">
        @foreach ([
            'all' => [__('Toutes'), $this->purchased()->concat($this->included())->unique('id')->count(), null],
            'purchased' => [__('Achetées'), $this->purchased()->count(), 'shopping_bag'],
            'included' => [__('Incluses au Pass'), $this->included()->count(), 'workspace_premium'],
        ] as $key => [$label, $count, $icon])
            @php($current = $filter === $key)
            <button type="button" wire:click="$set('filter', '{{ $key }}')"
                    @class([
                        'shrink-0 h-10 px-space-md rounded-full font-label-lg text-label-lg shadow-card transition-colors flex items-center gap-1.5',
                        'bg-primary text-on-primary' => $current,
                        'bg-surface-container-lowest text-text-secondary hover:text-text-primary' => ! $current,
                    ])>
                @if ($icon)<x-icon :name="$icon" size="16" />@endif
                <span>{{ $label }}</span>
                <span @class([
                    'text-[11px] px-1.5 py-0.5 rounded-full font-label-sm',
                    'bg-primary-container text-on-primary' => $current,
                    'bg-surface-container text-text-primary' => ! $current,
                ])>{{ $count }}</span>
            </button>
        @endforeach
    </div>

    {{-- Cartes --}}
    <div class="flex flex-col gap-space-lg lg:grid lg:grid-cols-2 xl:grid-cols-3 lg:gap-gutter">
        @forelse ($this->trainings() as $training)
            @php($bought = $this->wasPurchased($training))
            @php($first = $this->firstModuleTitle($training))

            <article class="bg-surface-container-lowest rounded-xl shadow-raised overflow-hidden flex flex-col" data-test="training-card">
                <div class="relative h-44 w-full bg-surface-container-high overflow-hidden">
                    @if ($training->hasCover())
                        <img src="{{ $training->coverUrl() }}" alt="{{ $training->title }}" loading="lazy" class="w-full h-full object-cover" />
                    @else
                        <div class="flex h-full w-full items-center justify-center text-surface-container-highest">
                            <x-icon name="school" size="40" />
                        </div>
                    @endif

                    <div class="absolute inset-0 bg-gradient-to-t from-on-surface/80 via-on-surface/20 to-transparent"></div>

                    <div class="absolute top-space-sm left-space-sm flex flex-wrap gap-space-xs items-center">
                        <span @class([
                            'font-label-sm text-label-sm px-2.5 py-1 rounded-full shadow-card flex items-center gap-1',
                            'bg-primary text-on-primary' => $bought,
                            'bg-tertiary-fixed text-on-tertiary-fixed' => ! $bought,
                        ])>
                            <x-icon :name="$bought ? 'shopping_bag' : 'workspace_premium'" size="14" />
                            {{ $bought ? __('Achetée') : __('Incluse au Pass') }}
                        </span>
                    </div>

                    <div class="absolute bottom-space-sm left-space-sm right-space-sm flex items-end justify-between gap-space-xs">
                        <span class="font-label-sm text-label-sm text-surface bg-on-surface/40 backdrop-blur-sm px-2 py-0.5 rounded">
                            {{ $training->format->label() }}
                        </span>
                        <span class="font-price-tag text-price-tag text-tertiary-fixed">
                            {{ trans_choice(':count module|:count modules', (int) $training->contents_count, ['count' => (int) $training->contents_count]) }}
                        </span>
                    </div>
                </div>

                <div class="p-space-md flex flex-col gap-space-sm flex-1">
                    <div>
                        <h2 class="font-headline-sm text-headline-sm text-text-primary leading-snug line-clamp-2">
                            {{ $training->title }}
                        </h2>
                        <div class="flex items-center gap-1.5 mt-1 text-text-secondary min-w-0">
                            <x-icon name="verified_user" size="18" class="text-secondary shrink-0" />
                            <span class="font-body-md text-body-md text-text-secondary truncate">
                                {{ $training->farmer->farmerProfile?->farm_name ?? $training->farmer->name }}
                            </span>
                        </div>
                    </div>

                    @if ($first)
                        <div class="bg-surface-container-low rounded-lg p-space-sm flex items-start gap-space-sm">
                            <span class="w-7 h-7 rounded-full bg-secondary-fixed flex items-center justify-center shrink-0 mt-0.5">
                                <x-icon name="play_circle" size="16" class="text-on-secondary-fixed-variant" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <span class="block font-label-sm text-label-sm text-secondary font-semibold uppercase tracking-wider">
                                    {{ __('Premier module') }}
                                </span>
                                <span class="font-body-md-bold text-body-md-bold text-text-primary truncate block">{{ $first }}</span>
                            </div>
                        </div>
                    @endif

                    <a href="{{ route('trainings.read', ['training' => $training->slug]) }}" wire:navigate
                       class="mt-auto w-full min-h-[48px] bg-primary hover:bg-primary-container text-on-primary font-label-lg text-label-lg rounded-full flex items-center justify-center gap-2 shadow-card transition-colors">
                        <span>{{ __('Ouvrir la formation') }}</span>
                        <x-icon name="arrow_forward" size="18" />
                    </a>
                </div>
            </article>
        @empty
            <div class="lg:col-span-full rounded-2xl bg-surface-container-lowest flex flex-col items-center gap-space-sm px-space-md py-space-2xl text-center shadow-card">
                <span class="flex w-14 h-14 items-center justify-center rounded-full bg-surface-container-low">
                    <x-icon name="school" size="28" class="text-text-secondary" />
                </span>
                <h2 class="font-headline-sm text-headline-sm text-text-primary">
                    {{ $filter === 'all' ? __('Votre bibliothèque est vide') : __('Rien dans cette catégorie') }}
                </h2>
                <p class="font-body-md text-body-md text-text-secondary">
                    {{ $filter === 'included'
                        ? __('Les formations incluses s\'ouvrent avec un Pass en cours de validité.')
                        : __('Les formations que vous achetez apparaissent ici, module par module.') }}
                </p>

                <a href="{{ route('trainings.index') }}" wire:navigate
                   class="mt-1 h-12 px-6 rounded-full bg-primary text-on-primary font-label-lg text-label-lg inline-flex items-center gap-1.5 shadow-card hover:bg-primary-container transition-colors">
                    <x-icon name="school" size="18" />
                    {{ __('Voir le catalogue des formations') }}
                </a>
            </div>
        @endforelse
    </div>
</div>

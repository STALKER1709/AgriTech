<div class="flex w-full flex-col gap-5 py-2">
    {{-- Screen header as in the Stitch training catalogue. --}}
    <section class="flex flex-col gap-2">
        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-stitch-primary/10 px-2.5 py-0.5 text-xs font-semibold text-stitch-primary">
                <flux:icon.academic-cap class="size-3.5" />
                {{ __('Académie AgriTech') }}
            </span>
            <span class="inline-flex items-center rounded-full bg-white px-2.5 py-0.5 text-xs text-stitch-muted shadow-card">
                {{ __('Cameroun') }}
            </span>
        </div>

        <h1 class="font-display text-2xl font-bold tracking-tight text-stitch-ink sm:text-3xl">
            {{ __('Formations agricoles & élevage') }}
        </h1>
        <p class="text-base text-stitch-muted">
            {{ __('Apprenez les meilleures techniques agronomiques auprès d\'experts camerounais.') }}
        </p>
    </section>

    {{-- Search --}}
    <div class="relative flex w-full items-center">
        <flux:icon.magnifying-glass class="pointer-events-none absolute left-3.5 size-5 text-stitch-muted" />
        <input
            type="search"
            wire:model.live.debounce.300ms="search"
            placeholder="{{ __('Rechercher une formation, un producteur…') }}"
            class="h-12 w-full rounded-xl border border-stitch-border bg-white pl-11 pr-4 text-base text-stitch-ink shadow-card placeholder:text-stitch-muted/60 focus:border-stitch-primary focus:outline-none focus:ring-2 focus:ring-stitch-primary/25"
        />
    </div>

    {{-- Format chips --}}
    <div class="no-scrollbar -mx-4 flex items-center gap-2 overflow-x-auto px-4 py-0.5">
        <button wire:click="$set('format', '')"
                @class(['stitch-chip', 'stitch-chip-active' => $format === ''])>
            {{ __('Tous formats') }}
        </button>

        @foreach ($this->formats() as $formatOption)
            <button wire:click="$set('format', '{{ $formatOption->value }}')"
                    @class(['stitch-chip', 'stitch-chip-active' => $format === $formatOption->value])>
                @if ($formatOption->value === 'pdf')
                    <flux:icon.document-text class="size-3.5 text-stitch-terra" />
                @else
                    <flux:icon.video-camera class="size-3.5 text-stitch-terra" />
                @endif
                {{ $formatOption->label() }}
            </button>
        @endforeach

        @if ($search !== '' || $format !== '')
            <flux:button size="sm" variant="ghost" wire:click="resetFilters" class="rounded-full">
                {{ __('Réinitialiser') }}
            </flux:button>
        @endif
    </div>

    {{-- Subscription promotion banner, textes de l'écran Stitch --}}
    <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-stitch-primary via-[#165a31] to-[#0d3f20] p-5 text-white shadow-raised">
        <span class="absolute -bottom-8 -right-8 size-36 rounded-full bg-stitch-gold-soft/15 blur-2xl" aria-hidden="true"></span>
        <span class="absolute right-4 top-2 text-6xl text-stitch-gold-soft/15" aria-hidden="true">
            <flux:icon.trophy class="size-16" />
        </span>

        <div class="relative z-10 flex flex-col gap-2">
            <span class="stitch-badge-gold w-fit">
                <flux:icon.sparkles class="size-3.5" />
                {{ __('Pass Formations Illimité') }}
            </span>
            <h2 class="font-display text-lg font-bold leading-snug sm:text-xl">
                {{ __('Accédez à plus de :count formations', ['count' => $this->includedCount()]) }}
            </h2>
            <p class="max-w-sm text-sm text-white/90">
                {{ __('Pour seulement') }}
                <span class="font-bold text-stitch-gold">5 000 FCFA / mois</span>
                {{ __('sans engagement.') }}
            </p>
            <div class="flex flex-wrap items-center gap-3 pt-1">
                <a href="{{ route('client.subscriptions') }}" wire:navigate
                   class="inline-flex h-11 items-center gap-2 rounded-full bg-stitch-terra px-5 text-sm font-bold text-white shadow-card transition hover:bg-stitch-terra/90">
                    {{ __("Découvrir l'abonnement") }}
                    <flux:icon.arrow-right class="size-4" />
                </a>
                <span class="text-xs font-semibold text-white/80">{{ __('30 jours offerts') }}</span>
                @auth
                    <a href="{{ route('client.trainings') }}" wire:navigate
                       class="inline-flex h-10 items-center gap-2 rounded-full border border-white/40 px-4 text-sm font-semibold text-white hover:bg-white/10">
                        {{ __('Mes formations') }}
                    </a>
                @endauth
            </div>
        </div>
    </section>

    {{-- En-tête du flux, façon écran Stitch --}}
    <div class="flex items-center justify-between">
        <h2 class="font-display text-base font-bold">{{ __('Formations disponibles') }}</h2>
        <span class="text-xs text-stitch-muted">{{ trans_choice(':count résultat affiché|:count résultats affichés', $trainings->total(), ['count' => $trainings->total()]) }}</span>
    </div>

    @if ($trainings->isEmpty())
        <div class="stitch-card flex flex-col items-center gap-2 p-8 text-center">
            <span class="flex size-14 items-center justify-center rounded-full bg-stitch-low text-stitch-muted">
                <flux:icon.academic-cap class="size-6" />
            </span>
            <flux:heading size="sm">{{ __('Aucune formation ne correspond') }}</flux:heading>
            <flux:text class="max-w-xs text-sm">
                {{ __('Essayez d\'élargir votre recherche ou de retirer un filtre.') }}
            </flux:text>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($trainings as $training)
                <a href="{{ route('trainings.show', ['training' => $training->slug]) }}" wire:navigate
                   class="stitch-card group flex flex-col overflow-hidden p-2.5 transition-shadow hover:shadow-raised"
                   data-test="training-card">
                    {{-- 16:9 thumbnail with the format chip overlaid. --}}
                    <div class="relative aspect-video w-full overflow-hidden rounded-lg bg-gradient-to-br from-stitch-primary/15 to-stitch-terra-soft/40">
                        @if ($training->hasCover())
                            <img src="{{ $training->coverUrl() }}" alt="{{ $training->title }}"
                                 loading="lazy"
                                 class="size-full object-cover transition-transform duration-300 group-hover:scale-105" />
                        @else
                            <div class="flex size-full items-center justify-center text-stitch-primary/40 transition-transform duration-300 group-hover:scale-105">
                                <flux:icon.academic-cap class="size-10" />
                            </div>
                        @endif

                        <span class="absolute left-2 top-2 inline-flex items-center gap-1 rounded-full bg-white/90 px-2 py-0.5 text-[10px] font-bold text-stitch-ink shadow-sm backdrop-blur-sm">
                            @if ($training->format->value === 'pdf')
                                <flux:icon.document-text class="size-3" />
                            @else
                                <flux:icon.video-camera class="size-3" />
                            @endif
                            {{ $training->format->label() }}
                        </span>

                        @if ($training->included_in_subscription)
                            <span class="stitch-badge-gold absolute bottom-2 left-2 text-[10px] shadow-sm">
                                <flux:icon.star class="size-3" />
                                {{ __('Incluse avec abonnement') }}
                            </span>
                        @endif
                    </div>

                    <div class="mt-2 flex min-w-0 flex-1 flex-col px-0.5">
                        <flux:heading size="sm" class="line-clamp-2 leading-tight">{{ $training->title }}</flux:heading>
                        <span class="mt-0.5 flex items-center gap-1 truncate text-xs text-stitch-muted">
                            <flux:icon.map-pin class="size-3 shrink-0" />
                            {{ $training->farmer->farmerProfile?->farm_name }}
                            @if ($training->farmer->farmerProfile?->region)
                                · {{ $training->farmer->farmerProfile->region }}
                            @endif
                        </span>
                    </div>

                    <div class="mt-1 flex items-end justify-between gap-2 px-0.5 pb-0.5 pt-2">
                        <span class="stitch-price text-base leading-tight">{{ $training->price->format() }}</span>
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-stitch-terra-soft text-stitch-terra-ink transition-colors group-hover:bg-stitch-terra group-hover:text-white">
                            <flux:icon.arrow-right class="size-4" />
                        </span>
                    </div>
                </a>
            @endforeach
        </div>

        {{ $trainings->links() }}
    @endif
</div>

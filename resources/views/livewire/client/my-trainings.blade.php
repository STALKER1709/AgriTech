<div class="flex w-full flex-1 flex-col gap-5">
    {{-- En-tête façon écran Stitch « Mes Formations » --}}
    <div class="flex items-center gap-3">
        <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-stitch-terra-soft text-stitch-terra">
            <flux:icon.academic-cap class="size-5" />
        </span>
        <div>
            <h1 class="text-xl font-bold">{{ __('Mes formations') }}</h1>
            <p class="text-sm text-stitch-muted">{{ __('Vos formations achetées et celles que votre abonnement ouvre.') }}</p>
        </div>
    </div>

    {{-- Bannière Pass active, façon liseré or de l'écran Stitch --}}
    @if ($this->included()->isNotEmpty())
        <div class="flex items-center gap-3 rounded-xl border border-stitch-gold/50 bg-[#fdf6e3] px-4 py-3 shadow-card">
            <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-stitch-gold text-stitch-gold-ink">
                <flux:icon.sparkles class="size-4" />
            </span>
            <div class="min-w-0">
                <p class="text-sm font-bold">{{ __('Pass Formations Actif') }}</p>
                <p class="truncate text-xs text-stitch-muted">
                    {{ __('Accès illimité aux formations incluses — gérez votre abonnement depuis l\'écran dédié.') }}
                </p>
            </div>
            <flux:button size="sm" variant="ghost" :href="route('client.subscriptions')" wire:navigate class="ml-auto shrink-0">
                {{ __('Gérer') }}
            </flux:button>
        </div>
    @endif

    <div class="flex flex-col gap-3">
        <h2 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-stitch-muted">
            <flux:icon.shopping-bag class="size-4 text-stitch-terra" />
            {{ __('Achetées') }}
        </h2>

        @if ($this->purchased()->isEmpty())
            <div class="stitch-card flex flex-col items-center gap-2 px-6 py-8 text-center">
                <p class="text-sm text-stitch-muted">{{ __('Aucune formation achetée pour l\'instant.') }}</p>
                <flux:button size="sm" variant="primary" :href="route('trainings.index')" wire:navigate>
                    {{ __('Parcourir les formations') }}
                </flux:button>
            </div>
        @else
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ($this->purchased() as $training)
                    <a href="{{ route('trainings.show', ['training' => $training->slug]) }}" wire:navigate
                       class="stitch-card flex flex-col gap-2 p-4 transition hover:shadow-raised">
                        <div class="flex items-center gap-2">
                            <span class="stitch-badge-warning">{{ $training->format->label() }}</span>
                            <span class="stitch-badge-success">{{ __('Achetée') }}</span>
                        </div>

                        <p class="font-display text-sm font-bold leading-snug">{{ $training->title }}</p>
                        <p class="truncate text-xs text-stitch-muted">{{ $training->farmer->farmerProfile?->farm_name }}</p>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    <div class="flex flex-col gap-3">
        <h2 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-stitch-muted">
            <flux:icon.sparkles class="size-4 text-stitch-gold" />
            {{ __('Incluses dans l\'abonnement') }}
        </h2>

        @if ($this->included()->isEmpty())
            <div class="stitch-card flex flex-col items-center gap-2 px-6 py-8 text-center">
                <p class="text-sm text-stitch-muted">
                    {{ __('Votre abonnement n\'est pas actif ou aucune formation n\'y est incluse.') }}
                </p>
                <flux:button size="sm" variant="primary" :href="route('client.subscriptions')" wire:navigate>
                    {{ __('Découvrir le Pass') }}
                </flux:button>
            </div>
        @else
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ($this->included() as $training)
                    <a href="{{ route('trainings.show', ['training' => $training->slug]) }}" wire:navigate
                       class="stitch-card flex flex-col gap-2 p-4 transition hover:shadow-raised">
                        <div class="flex items-center gap-2">
                            <span class="stitch-badge-warning">{{ $training->format->label() }}</span>
                            <span class="stitch-badge-gold">{{ __('Abonnement') }}</span>
                        </div>

                        <p class="font-display text-sm font-bold leading-snug">{{ $training->title }}</p>
                        <p class="truncate text-xs text-stitch-muted">{{ $training->farmer->farmerProfile?->farm_name }}</p>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>

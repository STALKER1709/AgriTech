<div class="flex w-full flex-1 flex-col gap-5">
    {{-- En-tête façon écran Stitch « Mes Formations Vendeur » --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-stitch-terra-soft text-stitch-terra">
                <flux:icon.academic-cap class="size-5" />
            </span>
            <div>
                <h1 class="text-xl font-bold">{{ __('Mes formations') }}</h1>
                <p class="text-sm text-stitch-muted">{{ __('Créez vos formations, déposez vos contenus, suivez leur publication.') }}</p>
            </div>
        </div>

        <flux:button variant="primary" :href="route('farmer.trainings.create')" wire:navigate data-test="new-training">
            <span class="inline-flex items-center gap-1.5">
                <flux:icon.plus class="size-4" />
                {{ __('Nouvelle formation') }}
            </span>
        </flux:button>
    </div>

    <div class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:flex-wrap sm:px-0">
        <button type="button" wire:click="$set('status', '')"
                @class(['stitch-chip', 'stitch-chip-active' => $status === ''])>
            {{ __('Tous') }}
        </button>
        @foreach ($this->statuses() as $statusOption)
            <button type="button" wire:click="$set('status', '{{ $statusOption->value }}')"
                    @class(['stitch-chip', 'stitch-chip-active' => $status === $statusOption->value])>
                {{ $statusOption->label() }}
            </button>
        @endforeach
    </div>

    @forelse ($trainings as $training)
        <div class="stitch-card flex flex-col gap-3 p-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <flux:heading class="truncate">{{ $training->title }}</flux:heading>
                    <p class="mt-1 text-sm text-stitch-muted">
                        {{ $training->format->label() }} ·
                        <span class="stitch-price text-sm">{{ $training->price->format() }}</span>
                        @if ($training->included_in_subscription)
                            · <span class="stitch-badge-gold">{{ __('Abonnement') }}</span>
                        @endif
                    </p>
                    <p class="text-sm text-stitch-muted">
                        {{ trans_choice('{0}Aucun contenu|{1}:count module|[2,*]:count modules', $training->contents_count, ['count' => $training->contents_count]) }}
                    </p>
                </div>

                @php($badge = match ($training->status) {
                    \App\Enums\PublicationStatus::Published => 'stitch-badge-success',
                    \App\Enums\PublicationStatus::Rejected => 'stitch-badge-danger',
                    \App\Enums\PublicationStatus::Archived => 'stitch-badge-warning',
                    default => 'stitch-badge-warning',
                })
                <span class="{{ $badge }}">{{ $training->status->label() }}</span>
            </div>

            @if ($training->rejection_reason)
                <div class="flex items-start gap-2 rounded-xl bg-stitch-danger-soft px-3 py-2 text-xs font-medium text-stitch-danger">
                    <flux:icon.x-circle class="mt-0.5 size-4 shrink-0" />
                    <span>
                        <strong>{{ __('Refusé par la modération') }} :</strong>
                        {{ $training->rejection_reason }}
                    </span>
                </div>
            @endif

            <div class="flex flex-wrap gap-2">
                @can('update', $training)
                    <flux:button size="sm" :href="route('farmer.trainings.edit', ['training' => $training->slug])" wire:navigate>
                        {{ __('Modifier') }}
                    </flux:button>
                @endcan

                @can('submit', $training)
                    <flux:button size="sm" variant="primary" wire:click="submit({{ $training->id }})" data-test="submit-training">
                        {{ __('Soumettre à publication') }}
                    </flux:button>
                @endcan

                @if ($training->status === \App\Enums\PublicationStatus::Published)
                    <flux:button size="sm" variant="ghost" :href="route('trainings.show', ['training' => $training->slug])" wire:navigate>
                        {{ __('Voir la page publique') }}
                    </flux:button>
                @endif

                @can('archive', $training)
                    <flux:button size="sm" variant="ghost" wire:click="archive({{ $training->id }})" data-test="archive-training">
                        {{ __('Archiver') }}
                    </flux:button>
                @endcan
            </div>
        </div>
    @empty
        <div class="stitch-card flex flex-col items-center gap-3 px-6 py-12 text-center">
            <span class="flex size-14 items-center justify-center rounded-full bg-stitch-low">
                <flux:icon.academic-cap class="size-7 text-stitch-muted" />
            </span>
            <h2 class="font-display text-lg font-bold">{{ __('Aucune formation pour l\'instant') }}</h2>
            <p class="max-w-xs text-sm text-stitch-muted">{{ __('Créez votre première formation pour la proposer aux clients.') }}</p>
        </div>
    @endforelse

    {{ $trainings->links() }}
</div>

<div class="flex w-full flex-1 flex-col gap-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <flux:heading size="xl" level="1">{{ __('Mes formations') }}</flux:heading>
            <flux:text class="mt-2">{{ __('Créez vos formations, déposez vos contenus, suivez leur publication.') }}</flux:text>
        </div>

        <flux:button variant="primary" :href="route('farmer.trainings.create')" wire:navigate data-test="new-training">
            {{ __('Nouvelle formation') }}
        </flux:button>
    </div>

    <flux:select wire:model.live="status" class="sm:max-w-xs">
        <flux:select.option value="">{{ __('Tous les statuts') }}</flux:select.option>
        @foreach ($this->statuses() as $statusOption)
            <flux:select.option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</flux:select.option>
        @endforeach
    </flux:select>

    @forelse ($trainings as $training)
        <div class="flex flex-col gap-3 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <flux:heading class="truncate">{{ $training->title }}</flux:heading>
                    <flux:text class="mt-1 text-sm">
                        {{ $training->format->label() }} · {{ $training->price->format() }}
                        @if ($training->included_in_subscription)
                            · {{ __('incluse dans l\'abonnement') }}
                        @endif
                    </flux:text>
                    <flux:text class="text-sm">
                        {{ trans_choice('{0}Aucun contenu|{1}:count module|[2,*]:count modules', $training->contents_count, ['count' => $training->contents_count]) }}
                    </flux:text>
                </div>

                <flux:badge>{{ $training->status->label() }}</flux:badge>
            </div>

            @if ($training->rejection_reason)
                <flux:callout icon="x-circle" variant="danger">
                    <flux:callout.heading>{{ __('Refusé par la modération') }}</flux:callout.heading>
                    <flux:callout.text>{{ $training->rejection_reason }}</flux:callout.text>
                </flux:callout>
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
        <flux:callout icon="academic-cap">
            <flux:callout.heading>{{ __('Aucune formation pour l\'instant') }}</flux:callout.heading>
            <flux:callout.text>{{ __('Créez votre première formation pour la proposer aux clients.') }}</flux:callout.text>
        </flux:callout>
    @endforelse

    {{ $trainings->links() }}
</div>

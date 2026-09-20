<div class="flex w-full flex-col gap-6 py-6">
    <div>
        <flux:heading size="xl" level="1">{{ __('Formations') }}</flux:heading>
        <flux:text class="mt-2">
            {{ __('Apprenez auprès des agriculteurs eux-mêmes : vidéos et guides pratiquement orientés.') }}
        </flux:text>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row">
        <flux:input
            wire:model.live.debounce.300ms="search"
            :placeholder="__('Rechercher une formation')"
            icon="magnifying-glass"
            class="flex-1"
        />

        <flux:select wire:model.live="format" class="sm:max-w-48">
            <flux:select.option value="">{{ __('Tous les formats') }}</flux:select.option>
            @foreach ($this->formats() as $formatOption)
                <flux:select.option value="{{ $formatOption->value }}">{{ $formatOption->label() }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    @if ($search !== '' || $format !== '')
        <div>
            <flux:button size="sm" variant="ghost" wire:click="resetFilters">{{ __('Réinitialiser') }}</flux:button>
        </div>
    @endif

    @if ($trainings->isEmpty())
        <flux:callout icon="magnifying-glass">
            <flux:callout.heading>{{ __('Aucune formation ne correspond') }}</flux:callout.heading>
            <flux:callout.text>{{ __('Essayez d\'élargir votre recherche ou de retirer un filtre.') }}</flux:callout.text>
        </flux:callout>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($trainings as $training)
                <a href="{{ route('trainings.show', ['training' => $training->slug]) }}" wire:navigate
                   class="flex flex-col overflow-hidden rounded-xl border border-neutral-200 transition hover:border-neutral-400 dark:border-neutral-700">
                    <div class="flex aspect-video w-full items-center justify-center bg-zinc-100 dark:bg-zinc-800">
                        <flux:icon.academic-cap class="size-10 text-zinc-400" />
                    </div>

                    <div class="flex flex-1 flex-col gap-1 p-3">
                        <div class="flex items-center gap-2">
                            <flux:badge size="sm" class="self-start">{{ $training->format->label() }}</flux:badge>

                            @if ($training->included_in_subscription)
                                <flux:badge size="sm" variant="lime" class="self-start">
                                    {{ __('Incluse dans l\'abonnement') }}
                                </flux:badge>
                            @endif
                        </div>

                        <flux:heading size="sm" class="mt-1">{{ $training->title }}</flux:heading>
                        <flux:text class="text-xs">
                            {{ $training->farmer->farmerProfile?->farm_name }}
                            @if ($training->farmer->farmerProfile?->region)
                                · {{ $training->farmer->farmerProfile->region }}
                            @endif
                        </flux:text>

                        <flux:heading class="mt-auto pt-2">{{ $training->price->format() }}</flux:heading>
                    </div>
                </a>
            @endforeach
        </div>

        {{ $trainings->links() }}
    @endif
</div>

<div class="flex w-full flex-1 flex-col gap-6">
    <div>
        <flux:heading size="xl" level="1">{{ __('Mes formations') }}</flux:heading>
        <flux:text class="mt-2">{{ __('Vos formations achetées et celles que votre abonnement ouvre.') }}</flux:text>
    </div>

    <div class="flex flex-col gap-3">
        <flux:heading size="sm">{{ __('Achetées') }}</flux:heading>

        @if ($this->purchased()->isEmpty())
            <flux:text class="text-sm text-zinc-500">
                {{ __('Aucune formation achetée pour l\'instant.') }}
                <a href="{{ route('trainings.index') }}" wire:navigate class="text-accent underline">{{ __('Parcourir les formations') }}</a>
            </flux:text>
        @else
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ($this->purchased() as $training)
                    <a href="{{ route('trainings.show', ['training' => $training->slug]) }}" wire:navigate
                       class="flex flex-col gap-1 rounded-xl border border-neutral-200 p-4 transition hover:border-neutral-400 dark:border-neutral-700">
                        <div class="flex items-center gap-2">
                            <flux:badge size="sm">{{ $training->format->label() }}</flux:badge>
                            <flux:badge size="sm" variant="success">{{ __('Achetée') }}</flux:badge>
                        </div>

                        <flux:heading size="sm" class="mt-1">{{ $training->title }}</flux:heading>
                        <flux:text class="text-xs">{{ $training->farmer->farmerProfile?->farm_name }}</flux:text>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    <flux:separator />

    <div class="flex flex-col gap-3">
        <flux:heading size="sm">{{ __('Incluses dans l\'abonnement') }}</flux:heading>

        @if ($this->included()->isEmpty())
            <flux:text class="text-sm text-zinc-500">
                {{ __('Votre abonnement n\'est pas actif ou aucune formation n\'y est incluse.') }}
                <a href="{{ route('client.subscriptions') }}" wire:navigate class="text-accent underline">{{ __('Gérer mon abonnement') }}</a>
            </flux:text>
        @else
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ($this->included() as $training)
                    <a href="{{ route('trainings.show', ['training' => $training->slug]) }}" wire:navigate
                       class="flex flex-col gap-1 rounded-xl border border-neutral-200 p-4 transition hover:border-neutral-400 dark:border-neutral-700">
                        <div class="flex items-center gap-2">
                            <flux:badge size="sm">{{ $training->format->label() }}</flux:badge>
                            <flux:badge size="sm" variant="lime">{{ __('Abonnement') }}</flux:badge>
                        </div>

                        <flux:heading size="sm" class="mt-1">{{ $training->title }}</flux:heading>
                        <flux:text class="text-xs">{{ $training->farmer->farmerProfile?->farm_name }}</flux:text>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>

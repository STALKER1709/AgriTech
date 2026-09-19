<div class="flex w-full flex-1 flex-col gap-6">
    <div>
        <flux:heading size="xl" level="1">{{ __('Administration') }}</flux:heading>
        <flux:text class="mt-2">{{ __('Vue d\'ensemble de la plateforme.') }}</flux:text>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <a href="{{ route('admin.farmers') }}" wire:navigate
           class="rounded-xl border border-neutral-200 p-4 transition hover:border-neutral-400 dark:border-neutral-700">
            <flux:text>{{ __('Comptes à valider') }}</flux:text>
            <flux:heading size="xl" class="mt-1">{{ $this->farmersAwaitingValidation() }}</flux:heading>
            <flux:text class="mt-1 text-xs">{{ __('Agriculteurs ayant payé leurs frais') }}</flux:text>
        </a>

        <a href="{{ route('admin.moderation') }}" wire:navigate
           class="rounded-xl border border-neutral-200 p-4 transition hover:border-neutral-400 dark:border-neutral-700">
            <flux:text>{{ __('Publications à modérer') }}</flux:text>
            <flux:heading size="xl" class="mt-1">{{ $this->publicationsAwaitingModeration() }}</flux:heading>
            <flux:text class="mt-1 text-xs">{{ __('Produits et formations soumis') }}</flux:text>
        </a>

        <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
            <flux:text>{{ __('Paiements en attente') }}</flux:text>
            <flux:heading size="xl" class="mt-1">{{ $this->paymentsAwaitingOutcome() }}</flux:heading>
            <flux:text class="mt-1 text-xs">{{ __('Sans réponse de l\'opérateur') }}</flux:text>
        </div>

        <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
            <flux:text>{{ __('Agriculteurs actifs') }}</flux:text>
            <flux:heading size="xl" class="mt-1">{{ $this->activeFarmers() }}</flux:heading>
        </div>

        <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
            <flux:text>{{ __('Clients actifs') }}</flux:text>
            <flux:heading size="xl" class="mt-1">{{ $this->clients() }}</flux:heading>
        </div>

        <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
            <flux:text>{{ __('Encaissé aujourd\'hui') }}</flux:text>
            <flux:heading size="xl" class="mt-1">{{ $this->collectedToday()->format() }}</flux:heading>
        </div>
    </div>
</div>

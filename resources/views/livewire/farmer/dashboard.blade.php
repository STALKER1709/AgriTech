<div class="flex w-full flex-1 flex-col gap-6">
    <div>
        <flux:heading size="xl" level="1">{{ __('Bonjour :name', ['name' => auth()->user()?->first_name]) }}</flux:heading>
        <flux:text class="mt-2">{{ __('Voici l\'état de votre activité aujourd\'hui.') }}</flux:text>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <flux:card>
            <flux:text class="text-sm">{{ __('Chiffre d\'affaires (commandes payées)') }}</flux:text>
            <flux:heading size="lg" class="mt-1">{{ $this->earnedTotal->format() }}</flux:heading>
            <flux:text class="mt-1 text-xs text-zinc-500">
                {{ __('dont :amount de commission plateforme', ['amount' => $this->commissionTotal->format()]) }}
            </flux:text>
        </flux:card>

        <flux:card>
            <flux:text class="text-sm">{{ __('À préparer') }}</flux:text>
            <flux:heading size="lg" class="mt-1">{{ $this->toPrepare }}</flux:heading>
            <flux:text class="mt-1 text-xs text-zinc-500">{{ __('sous-commandes en attente de préparation') }}</flux:text>
        </flux:card>

        <flux:card>
            <flux:text class="text-sm">{{ __('Catalogue') }}</flux:text>
            <flux:heading size="lg" class="mt-1">{{ $this->publishedProducts }}</flux:heading>
            <flux:text class="mt-1 text-xs text-zinc-500">
                {{ trans_choice('{0}produit publié|{1}:count produit publié|[2,*]:count produits publiés', $this->publishedProducts) }}
                · {{ $this->publishedTrainings }} {{ __('formation(s)') }}
            </flux:text>
        </flux:card>

        <flux:card>
            <flux:text class="text-sm">{{ __('Messages non lus') }}</flux:text>
            <flux:heading size="lg" class="mt-1">{{ $this->unreadMessages }}</flux:heading>
            <flux:text class="mt-1 text-xs text-zinc-500">{{ __('de vos clients') }}</flux:text>
        </flux:card>
    </div>

    <div class="flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <flux:heading size="sm">{{ __('À préparer maintenant') }}</flux:heading>

            <flux:button size="sm" variant="ghost" :href="route('farmer.orders')" wire:navigate>
                {{ __('Toutes les commandes') }}
            </flux:button>
        </div>

        @if ($this->pendingWork()->isEmpty())
            <flux:callout icon="check-circle" variant="success">
                <flux:callout.text>{{ __('Rien à préparer pour l\'instant. Profitez-en !') }}</flux:callout.text>
            </flux:callout>
        @else
            <div class="flex flex-col gap-2">
                @foreach ($this->pendingWork() as $subOrder)
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                        <div class="min-w-0">
                            <flux:heading size="sm" class="truncate">
                                {{ $subOrder->reference }} — {{ $subOrder->order->client->name }}
                            </flux:heading>
                            <flux:text class="mt-1 text-sm">
                                {{ $subOrder->items->map(fn ($item) => $item->product->name)->implode(', ') }}
                            </flux:text>
                        </div>

                        <div class="flex items-center gap-3">
                            <flux:heading size="sm">{{ $subOrder->subtotal_amount->format() }}</flux:heading>
                            <flux:button size="sm" variant="primary" :href="route('farmer.orders')" wire:navigate>
                                {{ __('Préparer') }}
                            </flux:button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="flex flex-wrap gap-3">
        <flux:button variant="primary" :href="route('farmer.products.create')" wire:navigate>
            {{ __('Nouveau produit') }}
        </flux:button>

        <flux:button variant="outline" :href="route('farmer.trainings.create')" wire:navigate>
            {{ __('Nouvelle formation') }}
        </flux:button>
    </div>
</div>

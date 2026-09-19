<div class="flex w-full flex-1 flex-col gap-6">
    <div>
        <flux:heading size="xl" level="1">{{ __('Mes commandes') }}</flux:heading>
        <flux:text class="mt-2">{{ __('Suivez vos achats, du paiement à la livraison.') }}</flux:text>
    </div>

    <flux:select wire:model.live="status" class="sm:max-w-xs">
        <flux:select.option value="">{{ __('Tous les statuts') }}</flux:select.option>
        @foreach ($this->statuses() as $statusOption)
            <flux:select.option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</flux:select.option>
        @endforeach
    </flux:select>

    @forelse ($orders as $order)
        <div class="flex flex-col gap-3 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <flux:heading class="truncate">{{ $order->reference }}</flux:heading>
                    <flux:text class="mt-1 text-sm">
                        {{ $order->created_at?->timezone(config('app.timezone'))->translatedFormat('d F Y à H:i') }}
                    </flux:text>
                    <flux:text class="text-sm">
                        {{ __(':count agriculteur(s)', ['count' => $order->subOrders->count()]) }}
                    </flux:text>
                </div>

                <div class="text-right">
                    <flux:heading size="sm">{{ $order->total_amount->format() }}</flux:heading>
                    <flux:badge class="mt-1">{{ $order->status->label() }}</flux:badge>
                </div>
            </div>

            <div>
                <flux:button size="sm" :href="route('client.orders.show', ['order' => $order->reference])" wire:navigate>
                    {{ __('Voir le détail') }}
                </flux:button>
            </div>
        </div>
    @empty
        <flux:callout icon="clipboard-document-list">
            <flux:callout.heading>{{ __('Aucune commande pour l\'instant') }}</flux:callout.heading>
            <flux:callout.text>{{ __('Vos achats apparaîtront ici dès votre première commande.') }}</flux:callout.text>
            <x-slot name="actions">
                <flux:button size="sm" variant="primary" :href="route('catalog.browse')" wire:navigate>
                    {{ __('Voir le catalogue') }}
                </flux:button>
            </x-slot>
        </flux:callout>
    @endforelse

    {{ $orders->links() }}
</div>

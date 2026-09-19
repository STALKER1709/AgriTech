<div class="flex w-full flex-1 flex-col gap-6">
    <div>
        <flux:heading size="xl" level="1">{{ __('Commandes reçues') }}</flux:heading>
        <flux:text class="mt-2">{{ __('Les commandes payées, à préparer puis à livrer.') }}</flux:text>
    </div>

    <flux:select wire:model.live="status" class="sm:max-w-xs">
        <flux:select.option value="">{{ __('Tous les statuts') }}</flux:select.option>
        @foreach ($this->statuses() as $statusOption)
            <flux:select.option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</flux:select.option>
        @endforeach
    </flux:select>

    @forelse ($subOrders as $subOrder)
        <div class="flex flex-col gap-3 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <flux:heading class="truncate">{{ $subOrder->reference }}</flux:heading>
                    <flux:text class="mt-1 text-sm">
                        {{ __('Client : :name', ['name' => $subOrder->order->client->name]) }}
                    </flux:text>
                    <flux:text class="text-sm">
                        {{ $subOrder->created_at?->timezone(config('app.timezone'))->translatedFormat('d F Y à H:i') }}
                    </flux:text>
                </div>

                <flux:badge>{{ $subOrder->status->label() }}</flux:badge>
            </div>

            <div class="flex flex-col gap-2">
                @foreach ($subOrder->items as $item)
                    <div class="flex flex-wrap items-baseline justify-between gap-2 border-t border-neutral-100 pt-2 dark:border-neutral-800">
                        <flux:text class="min-w-0 flex-1 truncate">
                            {{ $item->product->name }}
                            <span class="text-zinc-500">
                                × {{ $item->quantity->format() }} {{ $item->product->unit->shortLabel() }}
                            </span>
                        </flux:text>
                        <flux:text>{{ $item->line_total->format() }}</flux:text>
                    </div>
                @endforeach
            </div>

            <div class="flex flex-col gap-1 border-t border-neutral-100 pt-2 dark:border-neutral-800">
                <div class="flex items-center justify-between gap-3">
                    <flux:text>{{ __('Sous-total') }}</flux:text>
                    <flux:text>{{ $subOrder->subtotal_amount->format() }}</flux:text>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <flux:text>
                        {{ __('Commission (:rate %)', ['rate' => $subOrder->commission_rate_snapshot]) }}
                    </flux:text>
                    <flux:text>− {{ $subOrder->commission_amount->format() }}</flux:text>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <flux:heading size="sm">{{ __('Ce qui vous revient') }}</flux:heading>
                    <flux:heading size="sm" data-test="payout-{{ $subOrder->id }}">{{ $subOrder->farmerPayout()->format() }}</flux:heading>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                @can('prepare', $subOrder)
                    <flux:button size="sm" variant="primary" wire:click="prepare({{ $subOrder->id }})" data-test="prepare-{{ $subOrder->id }}">
                        {{ __('Passer en préparation') }}
                    </flux:button>
                @endcan

                @can('deliver', $subOrder)
                    <flux:button size="sm" wire:click="deliver({{ $subOrder->id }})" data-test="deliver-{{ $subOrder->id }}">
                        {{ __('Marquer comme livrée') }}
                    </flux:button>
                @endcan
            </div>
        </div>
    @empty
        <flux:callout icon="clipboard-document-list">
            <flux:callout.heading>{{ __('Aucune commande reçue') }}</flux:callout.heading>
            <flux:callout.text>{{ __('Une commande apparaît ici dès que son paiement est confirmé.') }}</flux:callout.text>
        </flux:callout>
    @endforelse

    {{ $subOrders->links() }}
</div>

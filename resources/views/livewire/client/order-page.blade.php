<div class="flex w-full flex-1 flex-col gap-6">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item :href="route('client.orders')" wire:navigate>{{ __('Mes commandes') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $order->reference }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <flux:heading size="xl" level="1">{{ $order->reference }}</flux:heading>
            <flux:text class="mt-2">
                {{ $order->created_at?->timezone(config('app.timezone'))->translatedFormat('d F Y à H:i') }}
            </flux:text>
        </div>

        <div class="text-right">
            <flux:heading size="lg" data-test="order-total">{{ $order->total_amount->format() }}</flux:heading>
            <flux:badge class="mt-1" data-test="order-status">{{ $order->status->label() }}</flux:badge>
        </div>
    </div>

    @if ($this->isAwaitingPayment())
        <div class="flex flex-col gap-4 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
            <div>
                <flux:heading size="sm">{{ __('Payer la commande') }}</flux:heading>
                <flux:text class="mt-1 text-sm">
                    {{ __('Paiement Mobile Money simulé. Aucun opérateur réel n\'est contacté.') }}
                </flux:text>
            </div>

            @if ($order->expires_at)
                <flux:callout icon="clock">
                    <flux:callout.text>
                        {{ __('À payer avant le :date, sans quoi la commande sera annulée.', [
                            'date' => $order->expires_at->timezone(config('app.timezone'))->translatedFormat('d F Y à H:i'),
                        ]) }}
                    </flux:callout.text>
                </flux:callout>
            @endif

            @if ($this->paymentInFlight())
                <flux:callout icon="arrow-path">
                    <flux:callout.text>
                        {{ __('Un paiement est déjà en cours de vérification pour cette commande.') }}
                    </flux:callout.text>
                    <x-slot name="actions">
                        <flux:button size="sm"
                                     :href="route('payments.pending', ['payment' => $this->paymentInFlight()->provider_reference])"
                                     wire:navigate>
                            {{ __('Suivre le paiement') }}
                        </flux:button>
                    </x-slot>
                </flux:callout>
            @endif

            <form wire:submit="pay" class="flex flex-col gap-4">
                <flux:select wire:model="method" :label="__('Opérateur')" data-test="method">
                    @foreach ($this->methods() as $methodOption)
                        <flux:select.option value="{{ $methodOption->value }}">{{ $methodOption->label() }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="phone" :label="__('Numéro de téléphone')" placeholder="+237 6XX XX XX XX" data-test="phone" />

                <flux:button type="submit" variant="primary" icon="credit-card" wire:loading.attr="disabled" data-test="pay">
                    {{ __('Payer :amount', ['amount' => $order->total_amount->format()]) }}
                </flux:button>
            </form>
        </div>
    @endif

    @foreach ($order->subOrders as $subOrder)
        <div class="flex flex-col gap-3 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <flux:heading size="sm" class="truncate">
                        {{ $subOrder->farmer->farmerProfile?->farm_name ?? $subOrder->farmer->name }}
                    </flux:heading>
                    <flux:text class="mt-1 text-sm">{{ $subOrder->reference }}</flux:text>
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

            <div class="flex items-center justify-between gap-3 border-t border-neutral-100 pt-2 dark:border-neutral-800">
                <flux:text>{{ __('Sous-total') }}</flux:text>
                <flux:heading size="sm">{{ $subOrder->subtotal_amount->format() }}</flux:heading>
            </div>
        </div>
    @endforeach
</div>

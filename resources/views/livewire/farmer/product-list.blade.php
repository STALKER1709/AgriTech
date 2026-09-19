<div class="flex w-full flex-1 flex-col gap-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <flux:heading size="xl" level="1">{{ __('Mes produits') }}</flux:heading>
            <flux:text class="mt-2">{{ __('Créez vos fiches, soumettez-les, suivez leur publication.') }}</flux:text>
        </div>

        <flux:button variant="primary" :href="route('farmer.products.create')" wire:navigate data-test="new-product">
            {{ __('Nouveau produit') }}
        </flux:button>
    </div>

    <flux:select wire:model.live="status" class="sm:max-w-xs">
        <flux:select.option value="">{{ __('Tous les statuts') }}</flux:select.option>
        @foreach ($this->statuses() as $statusOption)
            <flux:select.option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</flux:select.option>
        @endforeach
    </flux:select>

    @forelse ($products as $product)
        <div class="flex flex-col gap-3 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="flex min-w-0 gap-3">
                    <div class="size-16 shrink-0 overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800">
                        @if ($product->images->isNotEmpty())
                            <img src="{{ $product->images->first()->url() }}" alt="" class="size-full object-cover" />
                        @endif
                    </div>
                    <div class="min-w-0">
                        <flux:heading class="truncate">{{ $product->name }}</flux:heading>
                        <flux:text class="mt-1 text-sm">
                            {{ $product->category->name }} · {{ $product->unit_price->format() }} / {{ $product->unit->shortLabel() }}
                        </flux:text>
                        <flux:text class="text-sm">
                            {{ __('Stock : :quantity', ['quantity' => $product->stock_quantity->format()]) }}
                        </flux:text>
                    </div>
                </div>

                <flux:badge>{{ $product->status->label() }}</flux:badge>
            </div>

            @if ($product->rejection_reason)
                <flux:callout icon="x-circle" variant="danger">
                    <flux:callout.heading>{{ __('Refusé par la modération') }}</flux:callout.heading>
                    <flux:callout.text>{{ $product->rejection_reason }}</flux:callout.text>
                </flux:callout>
            @endif

            <div class="flex flex-wrap gap-2">
                @can('update', $product)
                    <flux:button size="sm" :href="route('farmer.products.edit', ['product' => $product->slug])" wire:navigate>
                        {{ __('Modifier') }}
                    </flux:button>
                @endcan

                @can('submit', $product)
                    <flux:button size="sm" variant="primary" wire:click="submit({{ $product->id }})" data-test="submit-product">
                        {{ __('Soumettre à publication') }}
                    </flux:button>
                @endcan

                @if ($product->status === \App\Enums\PublicationStatus::Published)
                    <flux:button size="sm" variant="ghost"
                                 :href="route('catalog.product', ['product' => $product->slug])" wire:navigate>
                        {{ __('Voir dans le catalogue') }}
                    </flux:button>
                @endif

                @can('archive', $product)
                    <flux:button size="sm" variant="ghost" wire:click="archive({{ $product->id }})" data-test="archive-product">
                        {{ __('Archiver') }}
                    </flux:button>
                @endcan
            </div>
        </div>
    @empty
        <flux:callout icon="squares-plus">
            <flux:callout.heading>{{ __('Aucun produit pour l\'instant') }}</flux:callout.heading>
            <flux:callout.text>{{ __('Créez votre première fiche produit pour la proposer au catalogue.') }}</flux:callout.text>
        </flux:callout>
    @endforelse

    {{ $products->links() }}
</div>

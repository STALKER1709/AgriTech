<div class="flex w-full flex-col gap-6 py-6">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item :href="route('catalog.browse')" wire:navigate>{{ __('Catalogue') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $product->name }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="flex flex-col gap-3">
            <div class="aspect-4/3 w-full overflow-hidden rounded-xl bg-zinc-100 dark:bg-zinc-800">
                @if ($product->images->isNotEmpty())
                    <img src="{{ $product->images->first()->url() }}" alt="{{ $product->name }}" class="size-full object-cover" />
                @else
                    <div class="flex size-full items-center justify-center">
                        <flux:icon.photo class="size-10 text-zinc-400" />
                    </div>
                @endif
            </div>

            @if ($product->images->count() > 1)
                <div class="grid grid-cols-4 gap-2">
                    @foreach ($product->images->skip(1) as $image)
                        <img src="{{ $image->url() }}" alt="" loading="lazy"
                             class="aspect-square w-full rounded-lg object-cover" />
                    @endforeach
                </div>
            @endif
        </div>

        <div class="flex flex-col gap-4">
            <div>
                <flux:badge size="sm">{{ $product->category->name }}</flux:badge>
                <flux:heading size="xl" level="1" class="mt-2">{{ $product->name }}</flux:heading>
            </div>

            <div>
                <flux:heading size="xl">{{ $product->unit_price->format() }}</flux:heading>
                <flux:text class="text-sm">{{ __('par :unit', ['unit' => $product->unit->label()]) }}</flux:text>
            </div>

            <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                <flux:text class="text-sm">{{ __('Vendu par') }}</flux:text>
                <flux:heading size="sm" class="mt-1">{{ $product->farmer->farmerProfile?->farm_name }}</flux:heading>
                <flux:text class="mt-1 text-sm">
                    {{ $product->farmer->farmerProfile?->city }}, {{ $product->farmer->farmerProfile?->region }}
                </flux:text>
            </div>

            <div>
                @if ($product->isInStock())
                    <flux:badge variant="success">
                        {{ __('En stock : :quantity :unit', [
                            'quantity' => $product->stock_quantity->format(),
                            'unit' => $product->unit->shortLabel(),
                        ]) }}
                    </flux:badge>
                @else
                    <flux:badge variant="danger">{{ __('Rupture de stock') }}</flux:badge>
                @endif
            </div>

            <flux:separator />

            <div>
                <flux:heading size="sm">{{ __('Description') }}</flux:heading>
                <flux:text class="mt-2 whitespace-pre-line">{{ $product->description }}</flux:text>
            </div>

            <flux:callout icon="shopping-cart">
                <flux:callout.text>
                    {{ __('L\'achat en ligne arrive à la prochaine étape du projet. En attendant, contactez l\'agriculteur par la messagerie.') }}
                </flux:callout.text>
            </flux:callout>
        </div>
    </div>
</div>

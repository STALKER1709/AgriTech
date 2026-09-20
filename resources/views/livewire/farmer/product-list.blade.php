<div class="flex w-full flex-1 flex-col gap-5">
    {{-- En-tête façon écran Stitch « Mes Produits » --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-stitch-primary/10 text-stitch-primary">
                <flux:icon.squares-plus class="size-5" />
            </span>
            <div>
                <h1 class="text-xl font-bold">{{ __('Mes produits') }}</h1>
                <p class="text-sm text-stitch-muted">{{ __('Créez vos fiches, soumettez-les, suivez leur publication.') }}</p>
            </div>
        </div>

        <flux:button variant="primary" :href="route('farmer.products.create')" wire:navigate data-test="new-product">
            <span class="inline-flex items-center gap-1.5">
                <flux:icon.plus class="size-4" />
                {{ __('Nouveau produit') }}
            </span>
        </flux:button>
    </div>

    {{-- Filtres statuts en chips pilules --}}
    <div class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:flex-wrap sm:px-0">
        <button type="button" wire:click="$set('status', '')"
                @class(['stitch-chip', 'stitch-chip-active' => $status === ''])>
            {{ __('Tous') }}
        </button>
        @foreach ($this->statuses() as $statusOption)
            <button type="button" wire:click="$set('status', '{{ $statusOption->value }}')"
                    @class(['stitch-chip', 'stitch-chip-active' => $status === $statusOption->value])>
                {{ $statusOption->label() }}
            </button>
        @endforeach
    </div>

    @forelse ($products as $product)
        <div class="stitch-card flex flex-col gap-3 p-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="flex min-w-0 gap-3">
                    <div class="size-16 shrink-0 overflow-hidden rounded-xl bg-stitch-container">
                        @if ($product->images->isNotEmpty())
                            <img src="{{ $product->images->first()->url() }}" alt="" class="size-full object-cover" />
                        @endif
                    </div>
                    <div class="min-w-0">
                        <flux:heading class="truncate">{{ $product->name }}</flux:heading>
                        <p class="mt-1 truncate text-sm text-stitch-muted">
                            {{ $product->category->name }} ·
                            <span class="stitch-price text-sm">{{ $product->unit_price->format() }}</span>
                            / {{ $product->unit->shortLabel() }}
                        </p>
                        <p class="text-sm text-stitch-muted">
                            {{ __('Stock : :quantity', ['quantity' => $product->stock_quantity->format()]) }}
                        </p>
                    </div>
                </div>

                @php($badge = match ($product->status) {
                    \App\Enums\PublicationStatus::Published => 'stitch-badge-success',
                    \App\Enums\PublicationStatus::Rejected => 'stitch-badge-danger',
                    \App\Enums\PublicationStatus::Archived => 'stitch-badge-warning',
                    default => 'stitch-badge-warning',
                })
                <span class="{{ $badge }}">{{ $product->status->label() }}</span>
            </div>

            @if ($product->rejection_reason)
                <div class="flex items-start gap-2 rounded-xl bg-stitch-danger-soft px-3 py-2 text-xs font-medium text-stitch-danger">
                    <flux:icon.x-circle class="mt-0.5 size-4 shrink-0" />
                    <span>
                        <strong>{{ __('Refusé par la modération') }} :</strong>
                        {{ $product->rejection_reason }}
                    </span>
                </div>
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
        <div class="stitch-card flex flex-col items-center gap-3 px-6 py-12 text-center">
            <span class="flex size-14 items-center justify-center rounded-full bg-stitch-low">
                <flux:icon.squares-plus class="size-7 text-stitch-muted" />
            </span>
            <h2 class="font-display text-lg font-bold">{{ __('Aucun produit pour l\'instant') }}</h2>
            <p class="max-w-xs text-sm text-stitch-muted">{{ __('Créez votre première fiche produit pour la proposer au catalogue.') }}</p>
        </div>
    @endforelse

    {{ $products->links() }}
</div>

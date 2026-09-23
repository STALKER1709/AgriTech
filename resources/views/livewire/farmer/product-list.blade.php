{{--
    Reproduction de `agritech_mes_produits` : sous-barre avec le compteur,
    champ de recherche, pastilles de statut comptées, puis une carte par
    fiche avec sa photo, son statut, sa bande de performance et ses actions.

    Écarts : la maquette annonce un chiffre du mois comparé au mois
    précédent, une région et une filière par carte, une « dernière vente il y
    a 3 h » et un bouton « Ajuster stock ». Rien ne conserve le total d'un
    mois passé, une fiche ne porte ni région ni filière, et le stock se
    modifie dans le formulaire — un deuxième chemin d'écriture pour la même
    valeur serait une occasion de les faire diverger. Les quantités vendues
    ce mois-ci, elles, se comptent vraiment.
--}}
@php($counts = $this->counts())
@php($sold = $this->soldThisMonth())

<div class="flex w-full flex-1 flex-col gap-space-md">
    {{-- Chapeau --}}
    <section class="flex flex-wrap items-center justify-between gap-space-sm">
        <div class="flex items-center gap-space-sm min-w-0">
            <span class="flex w-10 h-10 shrink-0 items-center justify-center rounded-full bg-primary-fixed text-primary">
                <x-icon name="inventory_2" size="22" />
            </span>
            <div class="min-w-0">
                <h1 class="font-headline-md text-headline-md text-text-primary tracking-tight">{{ __('Mes produits') }}</h1>
                <p class="font-label-sm text-label-sm text-text-secondary flex items-center gap-1">
                    <x-icon name="eco" size="14" class="text-primary" />
                    {{ trans_choice(':count fiche enregistrée|:count fiches enregistrées', $counts['all'], ['count' => $counts['all']]) }}
                </p>
            </div>
        </div>

        <a href="{{ route('farmer.products.create') }}" wire:navigate data-test="new-product"
           class="h-11 px-5 rounded-full bg-primary text-on-primary font-label-lg text-label-lg inline-flex items-center gap-1.5 shadow-card hover:bg-primary-container transition-colors shrink-0">
            <x-icon name="add" size="18" />
            {{ __('Nouveau produit') }}
        </a>
    </section>

    {{-- Recherche --}}
    <div class="relative flex items-center bg-surface-container-lowest rounded-xl shadow-card">
        <span class="pl-space-md text-text-secondary pointer-events-none">
            <x-icon name="search" size="20" />
        </span>
        <input type="search" wire:model.live.debounce.400ms="search"
               placeholder="{{ __('Rechercher une fiche…') }}"
               class="w-full h-12 bg-transparent pl-space-sm pr-10 font-body-md text-body-md text-text-primary placeholder:text-text-secondary focus:outline-none" />

        @if ($this->activeFilterCount() > 0)
            <button type="button" wire:click="resetFilters" aria-label="{{ __('Effacer les filtres') }}"
                    class="absolute right-2 w-8 h-8 rounded-full flex items-center justify-center text-text-secondary hover:text-text-primary hover:bg-surface-container-high transition-colors">
                <x-icon name="close" size="18" />
            </button>
        @endif
    </div>

    {{-- Pastilles de statut --}}
    <div class="-mx-margin lg:mx-0 px-margin lg:px-0 overflow-x-auto no-scrollbar flex items-center gap-2">
        <button type="button" wire:click="$set('status', '')"
                @class([
                    'shrink-0 h-9 px-4 rounded-full font-label-sm text-label-sm flex items-center gap-1.5 shadow-card transition-colors',
                    'bg-primary text-on-primary' => $status === '',
                    'bg-surface-container-lowest text-text-secondary hover:text-text-primary' => $status !== '',
                ])>
            {{ __('Tous') }}
            <span @class([
                'px-1.5 rounded-full text-[11px] font-bold',
                'bg-on-primary/20' => $status === '',
                'bg-surface-container text-text-primary' => $status !== '',
            ])>{{ $counts['all'] }}</span>
        </button>

        {{-- Un statut que ce catalogue n'a pas n'a pas de pastille. --}}
        @foreach ($this->statuses() as $statusOption)
            @continue($counts[$statusOption->value] === 0 && $status !== $statusOption->value)
            @php($current = $status === $statusOption->value)
            <button type="button" wire:click="$set('status', '{{ $statusOption->value }}')"
                    @class([
                        'shrink-0 h-9 px-4 rounded-full font-label-sm text-label-sm flex items-center gap-1.5 shadow-card transition-colors',
                        'bg-primary text-on-primary' => $current,
                        'bg-surface-container-lowest text-text-secondary hover:text-text-primary' => ! $current,
                    ])>
                {{ $statusOption->label() }}
                <span @class([
                    'px-1.5 rounded-full text-[11px] font-bold',
                    'bg-on-primary/20' => $current,
                    'bg-surface-container text-text-primary' => ! $current,
                ])>{{ $counts[$statusOption->value] }}</span>
            </button>
        @endforeach
    </div>

    {{-- Fiches --}}
    <div class="flex flex-col gap-space-md">
        @forelse ($products as $product)
            @php($badge = match ($product->status) {
                \App\Enums\PublicationStatus::Published => ['bg-[#e8f5e9] text-status-success', 'check_circle'],
                \App\Enums\PublicationStatus::Rejected => ['bg-[#ffebee] text-status-error', 'cancel'],
                \App\Enums\PublicationStatus::InReview => ['bg-[#fff3e0] text-status-warning', 'hourglass_top'],
                \App\Enums\PublicationStatus::Archived => ['bg-surface-container-high text-text-secondary', 'inventory_2'],
                \App\Enums\PublicationStatus::Draft => ['bg-surface-container text-text-secondary', 'edit_note'],
            })

            <article class="rounded-2xl bg-surface-container-lowest shadow-card overflow-hidden flex flex-col" data-test="product-row">
                <div class="p-space-md flex gap-space-sm">
                    <div class="relative w-20 h-20 rounded-xl overflow-hidden bg-surface-container shrink-0">
                        @if ($product->images->isNotEmpty())
                            <img src="{{ $product->images->first()->url() }}" alt="" loading="lazy" class="w-full h-full object-cover" />
                        @else
                            <div class="flex h-full w-full items-center justify-center text-surface-container-highest">
                                <x-icon name="photo_camera" size="24" />
                            </div>
                        @endif
                    </div>

                    <div class="min-w-0 flex-1 flex flex-col gap-1">
                        <div class="flex items-start justify-between gap-space-xs">
                            <h2 class="font-headline-sm text-headline-sm text-text-primary line-clamp-2">{{ $product->name }}</h2>

                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full font-label-sm text-label-sm shrink-0 {{ $badge[0] }}"
                                  data-test="product-status">
                                <x-icon :name="$badge[1]" size="14" />
                                {{ $product->status->label() }}
                            </span>
                        </div>

                        <span class="font-label-sm text-label-sm text-text-secondary truncate">{{ $product->category->name }}</span>

                        <div class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
                            <span class="font-price-tag text-price-tag text-primary">{{ $product->unit_price->format() }}</span>
                            <span class="font-label-sm text-label-sm text-text-secondary">/ {{ $product->unit->shortLabel() }}</span>
                            <span class="font-label-sm text-label-sm text-text-secondary">
                                · {{ __('stock :quantity', [
                                    'quantity' => $product->stock_quantity->format().' '.$product->unit->countLabel($product->stock_quantity),
                                ]) }}
                            </span>
                        </div>
                    </div>
                </div>

                @if ($product->rejection_reason)
                    <div class="mx-space-md mb-space-md flex items-start gap-2 rounded-xl bg-[#ffebee] px-3 py-2 font-label-sm text-label-sm text-status-error">
                        <x-icon name="report" size="16" class="shrink-0 mt-0.5" />
                        <span><strong>{{ __('Refusé par la modération') }} :</strong> {{ $product->rejection_reason }}</span>
                    </div>
                @endif

                {{-- Bande de performance : ce mois-ci, et rien d'autre. --}}
                <div class="px-space-md py-space-sm bg-surface-container-low flex items-center gap-1.5">
                    <x-icon name="sell" size="16" class="text-secondary shrink-0" />
                    <span class="font-label-sm text-label-sm text-text-secondary">
                        @if (isset($sold[$product->id]))
                            {{ __(':quantity vendus ce mois-ci', [
                                'quantity' => $sold[$product->id]->format().' '.$product->unit->countLabel($sold[$product->id]),
                            ]) }}
                        @else
                            {{ __('Aucune vente ce mois-ci') }}
                        @endif
                    </span>
                </div>

                {{-- Actions --}}
                <div class="px-space-md py-space-sm flex flex-wrap gap-2">
                    @can('update', $product)
                        <a href="{{ route('farmer.products.edit', ['product' => $product->slug]) }}" wire:navigate
                           class="h-10 px-4 rounded-full bg-surface-container text-text-primary font-label-lg text-label-lg inline-flex items-center gap-1.5 hover:bg-surface-container-high transition-colors">
                            <x-icon name="edit" size="16" />
                            {{ __('Modifier') }}
                        </a>
                    @endcan

                    @can('submit', $product)
                        <button type="button" wire:click="submit({{ $product->id }})" data-test="submit-product"
                                class="h-10 px-4 rounded-full bg-primary text-on-primary font-label-lg text-label-lg inline-flex items-center gap-1.5 shadow-card hover:bg-primary-container transition-colors">
                            <x-icon name="send" size="16" />
                            {{ __('Soumettre') }}
                        </button>
                    @endcan

                    @if ($product->status === \App\Enums\PublicationStatus::Published)
                        <a href="{{ route('catalog.product', ['product' => $product->slug]) }}" wire:navigate
                           class="h-10 px-4 rounded-full bg-surface-container text-text-primary font-label-lg text-label-lg inline-flex items-center gap-1.5 hover:bg-surface-container-high transition-colors">
                            <x-icon name="visibility" size="16" />
                            {{ __('Voir la fiche') }}
                        </a>
                    @endif

                    @can('archive', $product)
                        <button type="button" wire:click="archive({{ $product->id }})" data-test="archive-product"
                                class="h-10 px-4 rounded-full bg-surface-container text-text-secondary font-label-lg text-label-lg inline-flex items-center gap-1.5 hover:bg-surface-container-high transition-colors">
                            <x-icon name="archive" size="16" />
                            {{ __('Archiver') }}
                        </button>
                    @endcan
                </div>
            </article>
        @empty
            <div class="rounded-2xl bg-surface-container-lowest flex flex-col items-center gap-space-sm px-space-md py-space-2xl text-center shadow-card">
                <span class="flex w-14 h-14 items-center justify-center rounded-full bg-surface-container-low">
                    <x-icon name="inventory_2" size="28" class="text-text-secondary" />
                </span>
                <h2 class="font-headline-sm text-headline-sm text-text-primary">
                    {{ $this->activeFilterCount() > 0 ? __('Aucune fiche ne correspond') : __('Aucun produit pour l\'instant') }}
                </h2>
                <p class="font-body-md text-body-md text-text-secondary max-w-sm">
                    {{ $this->activeFilterCount() > 0
                        ? __('Essayez un autre mot, ou retirez le filtre de statut.')
                        : __('Créez votre première fiche pour la proposer au catalogue.') }}
                </p>

                <a href="{{ route('farmer.products.create') }}" wire:navigate
                   class="mt-1 h-12 px-6 rounded-full bg-primary text-on-primary font-label-lg text-label-lg inline-flex items-center gap-1.5 shadow-card hover:bg-primary-container transition-colors">
                    <x-icon name="add" size="18" />
                    {{ __('Nouveau produit') }}
                </a>
            </div>
        @endforelse

        @if ($products->hasPages())
            <div class="pt-space-xs">{{ $products->links() }}</div>
        @endif
    </div>
</div>

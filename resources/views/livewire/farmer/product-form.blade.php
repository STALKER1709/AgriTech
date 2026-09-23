{{--
    Reproduction de `agritech_formulaire_produit` : retour, bandeau de
    modération quand la fiche a été refusée, grille de photos, puis trois
    sections titrées — informations générales, prix et conditionnement,
    disponibilité.

    Écarts : la maquette nomme un « auditeur » et date son motif, propose un
    champ « variété », des unités composées (« sac de 50 kg », « cageot
    standard ») et une pastille « 3 photos nettes » qui juge la qualité des
    clichés. Le motif de refus est stocké mais pas son auteur ni sa date ;
    les unités sont celles de `ProductUnit`, qui décident aussi de la façon
    dont un stock s'écrit ; et rien n'évalue une photo.
--}}
<div class="flex w-full max-w-2xl flex-1 flex-col gap-space-md">
    {{-- Retour --}}
    <div class="flex items-center gap-space-sm min-w-0">
        <a href="{{ route('farmer.products') }}" wire:navigate aria-label="{{ __('Retour aux produits') }}"
           class="w-11 h-11 rounded-full flex items-center justify-center text-text-primary hover:bg-surface-container transition-colors shrink-0">
            <x-icon name="arrow_back" size="24" />
        </a>

        <div class="min-w-0">
            <h1 class="font-headline-md text-headline-md text-text-primary tracking-tight truncate">
                {{ $product?->exists ? __('Modifier le produit') : __('Nouveau produit') }}
            </h1>
            <p class="font-label-sm text-label-sm text-text-secondary">
                {{ __('Enregistrée en brouillon ; la soumission se fait depuis la liste.') }}
            </p>
        </div>
    </div>

    {{-- Motif du refus --}}
    @if ($product?->exists && $product->rejection_reason)
        <div class="rounded-xl bg-[#ffebee] p-space-md flex items-start gap-space-sm" data-test="rejection-reason">
            <span class="w-9 h-9 rounded-full bg-status-error text-on-error flex items-center justify-center shrink-0">
                <x-icon name="priority_high" size="18" />
            </span>
            <div class="min-w-0">
                <span class="font-body-md-bold text-body-md-bold text-status-error block">
                    {{ __('Fiche refusée à la modération') }}
                </span>
                <p class="font-body-md text-body-md text-text-primary leading-relaxed mt-0.5">
                    {{ $product->rejection_reason }}
                </p>
                <p class="font-label-sm text-label-sm text-text-secondary mt-1.5 flex items-start gap-1.5">
                    <x-icon name="tips_and_updates" size="15" class="shrink-0 mt-0.5" />
                    {{ __('Corrigez ce point puis soumettez à nouveau depuis « Mes produits ».') }}
                </p>
            </div>
        </div>
    @endif

    <form wire:submit="save" class="flex flex-col gap-space-md">
        {{-- Photos --}}
        <section class="rounded-2xl bg-surface-container-lowest p-space-md shadow-card flex flex-col gap-space-sm">
            <div class="flex items-center justify-between gap-space-sm">
                <div class="flex items-center gap-space-xs">
                    <x-icon name="photo_camera" size="20" class="text-primary" />
                    <h2 class="font-headline-sm text-headline-sm text-text-primary">{{ __('Photos du produit') }}</h2>
                </div>

                <span class="font-label-sm text-label-sm text-text-secondary shrink-0">
                    {{ __(':count au plus', ['count' => config('catalog.images.max_per_product')]) }}
                </span>
            </div>

            @if ($product?->exists && $this->images()->isNotEmpty())
                <div class="grid grid-cols-3 gap-space-xs sm:grid-cols-4">
                    @foreach ($this->images() as $image)
                        <div class="relative aspect-square rounded-xl overflow-hidden bg-surface-container">
                            <img src="{{ $image->url() }}" alt="" class="w-full h-full object-cover" />

                            <button type="button" wire:click="removeImage({{ $image->id }})"
                                    wire:confirm="{{ __('Supprimer cette image ?') }}"
                                    aria-label="{{ __('Supprimer cette image') }}"
                                    class="absolute top-1 right-1 w-7 h-7 rounded-full bg-status-error text-on-error flex items-center justify-center shadow-card hover:opacity-90 transition-opacity">
                                <x-icon name="close" size="16" />
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif

            <label class="flex flex-col items-center justify-center gap-1.5 rounded-xl border-2 border-dashed border-outline-variant bg-surface-container-low p-space-md cursor-pointer hover:bg-surface-container transition-colors">
                <x-icon name="add_a_photo" size="28" class="text-primary" />
                <span class="font-label-lg text-label-lg text-text-primary">{{ __('Ajouter des photos') }}</span>
                <span class="font-label-sm text-label-sm text-text-secondary text-center">
                    {{ __('JPEG, PNG ou WebP, :max Mo au plus par image.', [
                        'max' => (int) (config('catalog.images.max_kilobytes') / 1024),
                    ]) }}
                </span>
                <input type="file" wire:model="uploads" multiple accept="image/jpeg,image/png,image/webp" class="sr-only" />
            </label>

            <p wire:loading wire:target="uploads" class="font-label-sm text-label-sm text-primary flex items-center gap-1.5">
                <x-icon name="sync" size="15" />
                {{ __('Téléversement en cours…') }}
            </p>

            @error('uploads.*')
                <p class="flex items-center gap-1 font-label-sm text-label-sm text-status-error">
                    <x-icon name="error" size="14" />
                    {{ $message }}
                </p>
            @enderror
        </section>

        {{-- Informations générales --}}
        <section class="rounded-2xl bg-surface-container-lowest p-space-md shadow-card flex flex-col gap-space-md">
            <div class="flex items-center gap-space-xs">
                <x-icon name="description" size="20" class="text-primary" />
                <h2 class="font-headline-sm text-headline-sm text-text-primary">{{ __('Informations générales') }}</h2>
            </div>

            <x-form-field wire="name" :label="__('Nom du produit')" icon="label" required
                          :placeholder="__('Ex : Tomates fermes de Foumbot')" />

            <x-form-field wire="category_id" type="select" :label="__('Catégorie')" icon="category" required
                          :options="$this->categories()->pluck('name', 'id')->all()"
                          :placeholder="__('Choisir une catégorie')" />

            <x-form-field wire="description" type="textarea" :label="__('Description')" icon="notes" required
                          :rows="5"
                          :placeholder="__('Variété, mode de culture, conditionnement, zone de livraison…')"
                          :hint="__('C\'est ce que lit l\'acheteur avant de commander.')" />
        </section>

        {{-- Prix et conditionnement --}}
        <section class="rounded-2xl bg-surface-container-lowest p-space-md shadow-card flex flex-col gap-space-md">
            <div class="flex items-center gap-space-xs">
                <x-icon name="sell" size="20" class="text-primary" />
                <h2 class="font-headline-sm text-headline-sm text-text-primary">{{ __('Prix et conditionnement') }}</h2>
            </div>

            <div class="grid gap-space-md sm:grid-cols-2">
                <x-form-field wire="unit_price" :label="__('Prix unitaire')" suffix="FCFA" required
                              inputmode="numeric" placeholder="1 000"
                              :hint="__('Un entier, sans centimes.')" />

                <x-form-field wire="unit" type="select" :label="__('Unité de vente')" icon="straighten" required
                              :options="collect($this->units())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->all()" />
            </div>

            <x-form-field wire="stock_quantity" :label="__('Quantité disponible')" icon="inventory" required
                          inputmode="decimal" placeholder="12.500"
                          :hint="__('Jusqu\'à trois décimales. C\'est ce stock qui limite les commandes.')" />
        </section>

        {{-- Actions --}}
        <div class="flex flex-wrap gap-space-sm">
            <button type="submit" data-test="save-product" wire:loading.attr="disabled"
                    class="h-14 flex-1 min-w-[200px] rounded-full bg-primary text-on-primary font-label-lg text-label-lg flex items-center justify-center gap-2 shadow-raised hover:bg-primary-container transition-colors disabled:opacity-60">
                <x-icon name="save" size="20" />
                <span wire:loading.remove wire:target="save">{{ __('Enregistrer') }}</span>
                <span wire:loading wire:target="save">{{ __('Enregistrement…') }}</span>
            </button>

            <a href="{{ route('farmer.products') }}" wire:navigate
               class="h-14 px-6 rounded-full bg-surface-container text-text-primary font-label-lg text-label-lg inline-flex items-center justify-center gap-1.5 hover:bg-surface-container-high transition-colors">
                {{ __('Annuler') }}
            </a>
        </div>
    </form>
</div>

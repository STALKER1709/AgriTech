<div class="flex w-full max-w-2xl flex-1 flex-col gap-6">
    <div>
        <flux:heading size="xl" level="1">
            {{ $product?->exists ? __('Modifier le produit') : __('Nouveau produit') }}
        </flux:heading>
        <flux:text class="mt-2">
            {{ __('La fiche est enregistrée en brouillon. Vous la soumettrez à publication depuis la liste.') }}
        </flux:text>
    </div>

    <form wire:submit="save" class="flex flex-col gap-4">
        <flux:input wire:model="name" :label="__('Nom du produit')" type="text" required autofocus />

        <flux:select wire:model="category_id" :label="__('Catégorie')" :placeholder="__('Choisissez une catégorie')" required>
            @foreach ($this->categories() as $category)
                <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:textarea
            wire:model="description"
            :label="__('Description')"
            :description="__('Variété, mode de culture, conditionnement, zone de livraison…')"
            rows="5"
            required
        />

        <div class="grid gap-4 sm:grid-cols-2">
            <flux:input
                wire:model="unit_price"
                :label="__('Prix unitaire (FCFA)')"
                :description="__('Nombre entier, sans centimes.')"
                type="text"
                inputmode="numeric"
                required
            />

            <flux:select wire:model="unit" :label="__('Unité de vente')" required>
                @foreach ($this->units() as $unitOption)
                    <flux:select.option value="{{ $unitOption->value }}">{{ $unitOption->label() }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <flux:input
            wire:model="stock_quantity"
            :label="__('Quantité en stock')"
            :description="__('Jusqu\'à trois décimales, par exemple 12.500')"
            type="text"
            inputmode="decimal"
            required
        />

        @if ($product?->exists && $this->images()->isNotEmpty())
            <div>
                <flux:heading size="sm">{{ __('Images actuelles') }}</flux:heading>
                <div class="mt-2 grid grid-cols-3 gap-2 sm:grid-cols-4">
                    @foreach ($this->images() as $image)
                        <div class="relative">
                            <img src="{{ $image->url() }}" alt="" class="aspect-square w-full rounded-lg object-cover" />
                            <flux:button
                                size="xs"
                                variant="danger"
                                class="absolute end-1 top-1"
                                wire:click="removeImage({{ $image->id }})"
                                wire:confirm="{{ __('Supprimer cette image ?') }}"
                            >
                                ×
                            </flux:button>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <flux:input
            type="file"
            wire:model="uploads"
            multiple
            accept="image/jpeg,image/png,image/webp"
            :label="__('Ajouter des images')"
            :description="__('JPEG, PNG ou WebP, :max Mo maximum par image, :count images au plus.', [
                'max' => (int) (config('catalog.images.max_kilobytes') / 1024),
                'count' => config('catalog.images.max_per_product'),
            ])"
        />

        <div wire:loading wire:target="uploads">
            <flux:text class="text-sm">{{ __('Téléversement en cours…') }}</flux:text>
        </div>

        <div class="flex flex-wrap gap-2">
            <flux:button variant="primary" type="submit" data-test="save-product">
                <span wire:loading.remove wire:target="save">{{ __('Enregistrer') }}</span>
                <span wire:loading wire:target="save">{{ __('Enregistrement…') }}</span>
            </flux:button>

            <flux:button variant="ghost" :href="route('farmer.products')" wire:navigate>{{ __('Annuler') }}</flux:button>
        </div>
    </form>
</div>

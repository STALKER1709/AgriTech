{{--
    Les catégories du catalogue. Aucune maquette ne les dessine ; l'écran
    suit la grammaire des autres pages d'administration — chapeau, création
    en tête, puis une ligne par catégorie avec sa modification en place.
--}}
<div class="flex w-full max-w-3xl flex-1 flex-col gap-space-md">
    <x-admin-header icon="category"
                    :eyebrow="__('Catalogue')"
                    :title="__('Catégories')"
                    :subtitle="__('Renommer une catégorie ne change jamais son slug : des URL déjà partagées en dépendent.')" />

    {{-- Création --}}
    <form wire:submit="create" class="rounded-2xl bg-surface-container-lowest p-space-md shadow-card flex flex-col gap-space-sm sm:flex-row sm:items-end">
        <x-form-field wire="newName" :label="__('Nouvelle catégorie')" icon="add" required
                      :placeholder="__('Ex : Épices et condiments')" class="sm:flex-1" />

        <button type="submit" data-test="create-category"
                class="h-12 px-5 rounded-full bg-primary text-on-primary font-label-lg text-label-lg inline-flex items-center justify-center gap-1.5 shadow-card hover:bg-primary-container transition-colors shrink-0">
            <x-icon name="add" size="18" />
            {{ __('Ajouter') }}
        </button>
    </form>

    {{-- Liste --}}
    <div class="rounded-2xl bg-surface-container-lowest shadow-card overflow-hidden divide-y divide-surface-container">
        @forelse ($categories as $category)
            <div class="p-space-md" data-test="category-row">
                @if ($editing === $category->id)
                    <form wire:submit="rename" class="flex flex-col gap-space-sm sm:flex-row sm:items-end">
                        <x-form-field wire="editedName" :label="__('Nom de la catégorie')" icon="edit" required
                                      class="sm:flex-1" />

                        <div class="flex gap-space-sm shrink-0">
                            <button type="submit" data-test="rename-category"
                                    class="h-12 px-5 rounded-full bg-primary text-on-primary font-label-lg text-label-lg inline-flex items-center gap-1.5 shadow-card hover:bg-primary-container transition-colors">
                                <x-icon name="save" size="18" />
                                {{ __('Enregistrer') }}
                            </button>

                            <button type="button" wire:click="cancel"
                                    class="h-12 px-5 rounded-full bg-surface-container text-text-primary font-label-lg text-label-lg inline-flex items-center gap-1.5 hover:bg-surface-container-high transition-colors">
                                {{ __('Annuler') }}
                            </button>
                        </div>
                    </form>
                @else
                    <div class="flex items-center gap-space-sm">
                        <span class="w-10 h-10 rounded-full bg-primary-fixed text-primary flex items-center justify-center shrink-0">
                            <x-icon name="category" size="18" />
                        </span>

                        <div class="min-w-0 flex-1">
                            <span class="font-body-md-bold text-body-md-bold text-text-primary truncate block">{{ $category->name }}</span>
                            <span class="font-label-sm text-label-sm text-text-secondary truncate block">
                                {{ $category->slug }}
                                · {{ trans_choice(':count produit|:count produits', (int) $category->products_count, ['count' => (int) $category->products_count]) }}
                            </span>
                        </div>

                        <div class="flex gap-space-xs shrink-0">
                            <button type="button" wire:click="edit({{ $category->id }})" data-test="edit-category"
                                    aria-label="{{ __('Renommer') }}"
                                    class="w-10 h-10 rounded-full bg-surface-container text-text-primary flex items-center justify-center hover:bg-surface-container-high transition-colors">
                                <x-icon name="edit" size="18" />
                            </button>

                            <button type="button" wire:click="delete({{ $category->id }})" data-test="delete-category"
                                    wire:confirm="{{ __('Supprimer cette catégorie ?') }}"
                                    aria-label="{{ __('Supprimer') }}"
                                    class="w-10 h-10 rounded-full bg-surface-container text-status-error flex items-center justify-center hover:bg-surface-container-high transition-colors">
                                <x-icon name="delete" size="18" />
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <p class="p-space-md font-body-md text-body-md text-text-secondary">
                {{ __('Aucune catégorie. Créez-en une pour que les agriculteurs puissent publier.') }}
            </p>
        @endforelse
    </div>
</div>

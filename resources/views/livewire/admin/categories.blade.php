<div class="flex w-full max-w-2xl flex-1 flex-col gap-5">
    {{-- En-tête avec pastille, cohérent avec les autres écrans d'administration --}}
    <div class="flex items-center gap-3">
        <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-stitch-primary/10 text-stitch-primary">
            <flux:icon.tag class="size-5" />
        </span>
        <div>
            <h1 class="text-xl font-bold">{{ __('Catégories') }}</h1>
            <p class="text-sm text-stitch-muted">{{ __('Elles structurent le catalogue et ses filtres.') }}</p>
        </div>
    </div>

    <form wire:submit="create" class="stitch-card flex flex-col gap-3 p-4">
        <flux:input wire:model="newName" :label="__('Nouvelle catégorie')" type="text" required />
        <div>
            <flux:button variant="primary" type="submit" data-test="create-category">{{ __('Ajouter') }}</flux:button>
        </div>
    </form>

    <div class="flex flex-col gap-2">
        @foreach ($categories as $category)
            <div class="flex flex-wrap items-center justify-between gap-3 stitch-card p-3">
                @if ($editing === $category->id)
                    <form wire:submit="rename" class="flex w-full flex-col gap-2 sm:flex-row sm:items-end">
                        <flux:input wire:model="editedName" :label="__('Nom')" type="text" class="sm:flex-1" required />
                        <div class="flex gap-2">
                            <flux:button variant="primary" type="submit" data-test="rename-category">{{ __('Enregistrer') }}</flux:button>
                            <flux:button variant="ghost" type="button" wire:click="cancel">{{ __('Annuler') }}</flux:button>
                        </div>
                    </form>
                @else
                    <div class="min-w-0">
                        <flux:heading size="sm">{{ $category->name }}</flux:heading>
                        <flux:text class="text-xs">
                            {{ trans_choice('{0}Aucun produit|{1}:count produit|[2,*]:count produits', $category->products_count, ['count' => $category->products_count]) }}
                        </flux:text>
                    </div>

                    <div class="flex gap-2">
                        @can('update', $category)
                            <flux:button size="sm" wire:click="edit({{ $category->id }})" data-test="edit-category">
                                {{ __('Renommer') }}
                            </flux:button>
                        @endcan

                        @can('delete', $category)
                            <flux:button size="sm" variant="danger" wire:click="delete({{ $category->id }})"
                                         wire:confirm="{{ __('Supprimer cette catégorie ?') }}" data-test="delete-category">
                                {{ __('Supprimer') }}
                            </flux:button>
                        @else
                            <flux:text class="self-center text-xs">{{ __('Contient des produits') }}</flux:text>
                        @endcan
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>

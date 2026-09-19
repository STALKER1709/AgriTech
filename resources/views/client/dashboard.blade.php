<x-layouts::app :title="__('Espace client')">
    <div class="flex w-full flex-1 flex-col gap-6">
        <div>
            <flux:heading size="xl" level="1">{{ __('Espace client') }}</flux:heading>
            <flux:text class="mt-2">{{ __('Retrouvez vos commandes, vos formations et vos échanges avec les agriculteurs.') }}</flux:text>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <flux:card>
                <flux:heading size="sm">{{ __('Catalogue') }}</flux:heading>
                <flux:text class="mt-1 text-sm">{{ __('Les produits des agriculteurs vérifiés.') }}</flux:text>
                <flux:button class="mt-3" size="sm" :href="route('catalog.browse')" wire:navigate>
                    {{ __('Parcourir') }}
                </flux:button>
            </flux:card>

            <flux:card>
                <flux:heading size="sm">{{ __('Mon panier') }}</flux:heading>
                <flux:text class="mt-1 text-sm">
                    {{ __(':count produit(s) en attente.', ['count' => auth()->user()?->cartItemCount() ?? 0]) }}
                </flux:text>
                <flux:button class="mt-3" size="sm" :href="route('client.cart')" wire:navigate>
                    {{ __('Ouvrir le panier') }}
                </flux:button>
            </flux:card>

            <flux:card>
                <flux:heading size="sm">{{ __('Mes commandes') }}</flux:heading>
                <flux:text class="mt-1 text-sm">{{ __('Paiement, préparation, livraison.') }}</flux:text>
                <flux:button class="mt-3" size="sm" :href="route('client.orders')" wire:navigate>
                    {{ __('Voir mes commandes') }}
                </flux:button>
            </flux:card>
        </div>

        <flux:callout icon="information-circle">
            <flux:callout.heading>{{ __('Formations et messagerie') }}</flux:callout.heading>
            <flux:callout.text>
                {{ __('Ces écrans arrivent dans les prochaines étapes du projet.') }}
            </flux:callout.text>
        </flux:callout>
    </div>
</x-layouts::app>

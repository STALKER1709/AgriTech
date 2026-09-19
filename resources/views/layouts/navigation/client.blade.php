<flux:sidebar.group :heading="__('Espace client')" class="grid">
    <flux:sidebar.item icon="home" :href="route('client.dashboard')" :current="request()->routeIs('client.dashboard')" wire:navigate>
        {{ __('Tableau de bord') }}
    </flux:sidebar.item>

    <flux:sidebar.item icon="squares-2x2" :href="route('catalog.browse')" :current="request()->routeIs('catalog.*')" wire:navigate>
        {{ __('Catalogue') }}
    </flux:sidebar.item>

    <flux:sidebar.item icon="shopping-cart" :href="route('client.cart')" :current="request()->routeIs('client.cart')" wire:navigate
                       :badge="auth()->user()?->cartItemCount() ?: null">
        {{ __('Mon panier') }}
    </flux:sidebar.item>

    <flux:sidebar.item icon="clipboard-document-list" :href="route('client.orders')" :current="request()->routeIs('client.orders*')" wire:navigate>
        {{ __('Mes commandes') }}
    </flux:sidebar.item>
</flux:sidebar.group>

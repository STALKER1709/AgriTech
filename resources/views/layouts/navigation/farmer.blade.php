<flux:sidebar.group :heading="__('Espace agriculteur')" class="grid">
    <flux:sidebar.item icon="home" :href="route('farmer.dashboard')" :current="request()->routeIs('farmer.dashboard')" wire:navigate>
        {{ __('Tableau de bord') }}
    </flux:sidebar.item>

    <flux:sidebar.item icon="squares-plus" :href="route('farmer.products')" :current="request()->routeIs('farmer.products*')" wire:navigate>
        {{ __('Mes produits') }}
    </flux:sidebar.item>

    <flux:sidebar.item icon="clipboard-document-list" :href="route('farmer.orders')" :current="request()->routeIs('farmer.orders*')" wire:navigate>
        {{ __('Commandes reçues') }}
    </flux:sidebar.item>
</flux:sidebar.group>

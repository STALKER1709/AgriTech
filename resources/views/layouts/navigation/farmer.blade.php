<flux:sidebar.group :heading="__('Espace agriculteur')" class="grid">
    <flux:sidebar.item icon="home" :href="route('farmer.dashboard')" :current="request()->routeIs('farmer.dashboard')" wire:navigate>
        {{ __('Tableau de bord') }}
    </flux:sidebar.item>

    <flux:sidebar.item icon="squares-plus" :href="route('farmer.products')" :current="request()->routeIs('farmer.products*')" wire:navigate>
        {{ __('Mes produits') }}
    </flux:sidebar.item>

    <flux:sidebar.item icon="academic-cap" :href="route('farmer.trainings')" :current="request()->routeIs('farmer.trainings*')" wire:navigate>
        {{ __('Mes formations') }}
    </flux:sidebar.item>

    <flux:sidebar.item icon="clipboard-document-list" :href="route('farmer.orders')" :current="request()->routeIs('farmer.orders*')" wire:navigate>
        {{ __('Commandes reçues') }}
    </flux:sidebar.item>

    <flux:sidebar.item icon="chat-bubble-left-right" :href="route('farmer.messages')" :current="request()->routeIs('farmer.messages*')" wire:navigate
                       :badge="app(\App\Services\Messaging\MessagingService::class)->unreadTotalFor(auth()->user()) ?: null">
        {{ __('Messages') }}
    </flux:sidebar.item>
</flux:sidebar.group>

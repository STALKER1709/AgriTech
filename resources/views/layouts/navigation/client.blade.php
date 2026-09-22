{{-- Navigation latérale du client, vocabulaire d'icônes des maquettes. --}}
<x-nav-item icon="space_dashboard" :href="route('client.dashboard')" :current="request()->routeIs('client.dashboard')">
    {{ __('Tableau de bord') }}
</x-nav-item>

<x-nav-item icon="storefront" :href="route('catalog.browse')" :current="request()->routeIs('catalog.*')">
    {{ __('Catalogue') }}
</x-nav-item>

<x-nav-item icon="school" :href="route('trainings.index')" :current="request()->routeIs('trainings.*')">
    {{ __('Formations') }}
</x-nav-item>

<x-nav-item icon="bookmark" :href="route('client.trainings')" :current="request()->routeIs('client.trainings')">
    {{ __('Mes formations') }}
</x-nav-item>

<x-nav-item icon="workspace_premium" :href="route('client.subscriptions')" :current="request()->routeIs('client.subscriptions')">
    {{ __('Abonnement') }}
</x-nav-item>

<x-nav-item icon="chat" :href="route('client.messages')" :current="request()->routeIs('client.messages*')"
            :badge="app(\App\Services\Messaging\MessagingService::class)->unreadTotalFor(auth()->user()) ?: null">
    {{ __('Messages') }}
</x-nav-item>

<x-nav-item icon="shopping_cart" :href="route('client.cart')" :current="request()->routeIs('client.cart')"
            :badge="auth()->user()?->cartItemCount() ?: null">
    {{ __('Mon panier') }}
</x-nav-item>

<x-nav-item icon="receipt_long" :href="route('client.orders')" :current="request()->routeIs('client.orders*')">
    {{ __('Mes commandes') }}
</x-nav-item>

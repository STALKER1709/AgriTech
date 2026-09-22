{{-- Navigation latérale de l'agriculteur, d'après l'écran
     `agritech_tableau_de_bord_vendeur`. --}}
<x-nav-item icon="space_dashboard" :href="route('farmer.dashboard')" :current="request()->routeIs('farmer.dashboard')">
    {{ __('Tableau de bord') }}
</x-nav-item>

<x-nav-item icon="inventory_2" :href="route('farmer.products')" :current="request()->routeIs('farmer.products*')">
    {{ __('Mes produits') }}
</x-nav-item>

<x-nav-item icon="school" :href="route('farmer.trainings')" :current="request()->routeIs('farmer.trainings*')">
    {{ __('Mes formations') }}
</x-nav-item>

<x-nav-item icon="local_shipping" :href="route('farmer.orders')" :current="request()->routeIs('farmer.orders*')">
    {{ __('Commandes reçues') }}
</x-nav-item>

<x-nav-item icon="chat" :href="route('farmer.messages')" :current="request()->routeIs('farmer.messages*')"
            :badge="app(\App\Services\Messaging\MessagingService::class)->unreadTotalFor(auth()->user()) ?: null">
    {{ __('Messages') }}
</x-nav-item>

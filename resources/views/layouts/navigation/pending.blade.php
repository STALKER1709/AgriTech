{{-- Compte pas encore actif : son espace est fermé, seul le statut est ouvert. --}}
<x-nav-item icon="hourglass_top" :href="route('account.status')" :current="request()->routeIs('account.status')">
    {{ __('Statut de mon compte') }}
</x-nav-item>

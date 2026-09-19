<flux:sidebar.group :heading="__('Espace client')" class="grid">
    <flux:sidebar.item icon="home" :href="route('client.dashboard')" :current="request()->routeIs('client.dashboard')" wire:navigate>
        {{ __('Tableau de bord') }}
    </flux:sidebar.item>
</flux:sidebar.group>

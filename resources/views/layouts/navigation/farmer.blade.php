<flux:sidebar.group :heading="__('Espace agriculteur')" class="grid">
    <flux:sidebar.item icon="home" :href="route('farmer.dashboard')" :current="request()->routeIs('farmer.dashboard')" wire:navigate>
        {{ __('Tableau de bord') }}
    </flux:sidebar.item>
</flux:sidebar.group>

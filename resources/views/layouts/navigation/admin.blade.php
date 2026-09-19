<flux:sidebar.group :heading="__('Administration')" class="grid">
    <flux:sidebar.item icon="home" :href="route('admin.dashboard')" :current="request()->routeIs('admin.dashboard')" wire:navigate>
        {{ __('Tableau de bord') }}
    </flux:sidebar.item>
</flux:sidebar.group>

<flux:sidebar.group :heading="__('Administration')" class="grid">
    <flux:sidebar.item icon="home" :href="route('admin.dashboard')" :current="request()->routeIs('admin.dashboard')" wire:navigate>
        {{ __('Tableau de bord') }}
    </flux:sidebar.item>

    <flux:sidebar.item icon="user-plus" :href="route('admin.farmers')" :current="request()->routeIs('admin.farmers')" wire:navigate>
        {{ __('Comptes à valider') }}
    </flux:sidebar.item>

    <flux:sidebar.item icon="users" :href="route('admin.users')" :current="request()->routeIs('admin.users')" wire:navigate>
        {{ __('Utilisateurs') }}
    </flux:sidebar.item>

    @can('publications.moderate')
        <flux:sidebar.item icon="check-badge" :href="route('admin.moderation')" :current="request()->routeIs('admin.moderation')" wire:navigate>
            {{ __('Publications à modérer') }}
        </flux:sidebar.item>
    @endcan

    @can('categories.manage')
        <flux:sidebar.item icon="tag" :href="route('admin.categories')" :current="request()->routeIs('admin.categories')" wire:navigate>
            {{ __('Catégories') }}
        </flux:sidebar.item>
    @endcan

    @can('privileges.manage')
        <flux:sidebar.item icon="key" :href="route('admin.privileges')" :current="request()->routeIs('admin.privileges')" wire:navigate>
            {{ __('Privilèges') }}
        </flux:sidebar.item>
    @endcan

    @can('settings.manage')
        <flux:sidebar.item icon="cog" :href="route('admin.settings')" :current="request()->routeIs('admin.settings')" wire:navigate>
            {{ __('Paramètres') }}
        </flux:sidebar.item>
    @endcan

    @can('audit.view')
        <flux:sidebar.item icon="document-text" :href="route('admin.audit')" :current="request()->routeIs('admin.audit')" wire:navigate>
            {{ __('Journal d\'audit') }}
        </flux:sidebar.item>
    @endcan
</flux:sidebar.group>

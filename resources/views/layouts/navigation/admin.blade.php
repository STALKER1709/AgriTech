<flux:sidebar.group :heading="__('Back-office Central')" class="grid">
    {{-- Sidebar du design Stitch : Tableau de bord, Agriculteurs à valider
         (badge), Utilisateurs, Modération (badge), Privilèges, Journal
         d'audit, Paramètres — dans cet ordre exact. Chaque entrée reste
         derrière son Gate : un admin sans le privilège ne voit pas l'onglet
         et ne peut pas non plus appeler la page. --}}
    <flux:sidebar.item icon="home" :href="route('admin.dashboard')" :current="request()->routeIs('admin.dashboard')" wire:navigate>
        {{ __('Tableau de bord') }}
    </flux:sidebar.item>

    @can(\App\Models\Privilege::APPROVE_FARMERS)
        <flux:sidebar.item icon="user-plus" :href="route('admin.farmers')" :current="request()->routeIs('admin.farmers')" wire:navigate>
            <span class="flex w-full items-center justify-between gap-2">
                {{ __('Agriculteurs à valider') }}
                @php($pendingFarmers = \App\Models\User::query()->where('role', \App\Enums\UserRole::Farmer)->where('status', \App\Enums\UserStatus::PendingValidation)->count())
                @if ($pendingFarmers > 0)
                    <span class="grid h-5 min-w-5 place-items-center rounded-full bg-stitch-terra px-1.5 text-[11px] font-bold leading-none text-white">{{ $pendingFarmers }}</span>
                @endif
            </span>
        </flux:sidebar.item>
    @endcan

    <flux:sidebar.item icon="users" :href="route('admin.users')" :current="request()->routeIs('admin.users')" wire:navigate>
        {{ __('Utilisateurs') }}
    </flux:sidebar.item>

    @can(\App\Models\Privilege::MODERATE_PUBLICATIONS)
        <flux:sidebar.item icon="check-badge" :href="route('admin.moderation')" :current="request()->routeIs('admin.moderation')" wire:navigate>
            <span class="flex w-full items-center justify-between gap-2">
                {{ __('Modération') }}
                @php($pendingModeration = \App\Models\Product::query()->where('status', \App\Enums\PublicationStatus::InReview)->count() + \App\Models\Training::query()->where('status', \App\Enums\PublicationStatus::InReview)->count())
                @if ($pendingModeration > 0)
                    <span class="grid h-5 min-w-5 place-items-center rounded-full bg-stitch-terra px-1.5 text-[11px] font-bold leading-none text-white">{{ $pendingModeration }}</span>
                @endif
            </span>
        </flux:sidebar.item>
    @endcan

    @can(\App\Models\Privilege::MANAGE_PRIVILEGES)
        <flux:sidebar.item icon="key" :href="route('admin.privileges')" :current="request()->routeIs('admin.privileges')" wire:navigate>
            {{ __('Privilèges') }}
        </flux:sidebar.item>
    @endcan

    @can(\App\Models\Privilege::VIEW_AUDIT_LOG)
        <flux:sidebar.item icon="document-text" :href="route('admin.audit')" :current="request()->routeIs('admin.audit')" wire:navigate>
            {{ __("Journal d'audit") }}
        </flux:sidebar.item>
    @endcan

    @can(\App\Models\Privilege::MANAGE_SETTINGS)
        <flux:sidebar.item icon="cog" :href="route('admin.settings')" :current="request()->routeIs('admin.settings')" wire:navigate>
            {{ __('Paramètres') }}
        </flux:sidebar.item>
    @endcan
</flux:sidebar.group>

{{-- Back-office : ordre et icônes repris tels quels de l'écran
     `agritech_admin_tableau_de_bord`. Chaque entrée reste derrière son Gate :
     un administrateur sans le privilège ne voit pas l'onglet et ne peut pas
     non plus atteindre la page. --}}
<x-nav-item icon="space_dashboard" :href="route('admin.dashboard')" :current="request()->routeIs('admin.dashboard')">
    {{ __('Tableau de bord') }}
</x-nav-item>

@can(\App\Models\Privilege::APPROVE_FARMERS)
    @php($pendingFarmers = \App\Models\User::query()
        ->where('role', \App\Enums\UserRole::Farmer)
        ->where('status', \App\Enums\UserStatus::PendingValidation)
        ->count())
    <x-nav-item icon="how_to_reg" :href="route('admin.farmers')" :current="request()->routeIs('admin.farmers')"
                :badge="$pendingFarmers ?: null">
        {{ __('Agriculteurs à valider') }}
    </x-nav-item>
@endcan

@can(\App\Models\Privilege::SUSPEND_USERS)
    <x-nav-item icon="group" :href="route('admin.users')" :current="request()->routeIs('admin.users')">
        {{ __('Utilisateurs') }}
    </x-nav-item>
@endcan

@can(\App\Models\Privilege::MODERATE_PUBLICATIONS)
    @php($pendingPublications = \App\Models\Product::query()->where('status', \App\Enums\PublicationStatus::InReview)->count()
        + \App\Models\Training::query()->where('status', \App\Enums\PublicationStatus::InReview)->count())
    <x-nav-item icon="gavel" :href="route('admin.moderation')" :current="request()->routeIs('admin.moderation')"
                :badge="$pendingPublications ?: null" badge-variant="error">
        {{ __('Modération') }}
    </x-nav-item>
@endcan

@can(\App\Models\Privilege::MANAGE_PRIVILEGES)
    <x-nav-item icon="admin_panel_settings" :href="route('admin.privileges')" :current="request()->routeIs('admin.privileges')">
        {{ __('Privilèges') }}
    </x-nav-item>
@endcan

@can(\App\Models\Privilege::VIEW_AUDIT_LOG)
    <x-nav-item icon="receipt_long" :href="route('admin.audit')" :current="request()->routeIs('admin.audit')">
        {{ __('Journal d\'audit') }}
    </x-nav-item>
@endcan

@can(\App\Models\Privilege::MANAGE_SETTINGS)
    <x-nav-item icon="settings" :href="route('admin.settings')" :current="request()->routeIs('admin.settings')">
        {{ __('Paramètres') }}
    </x-nav-item>
@endcan

@can(\App\Models\Privilege::MANAGE_CATEGORIES)
    <x-nav-item icon="category" :href="route('admin.categories')" :current="request()->routeIs('admin.categories')">
        {{ __('Catégories') }}
    </x-nav-item>
@endcan

<x-nav-item icon="notifications" :href="route('notifications')" :current="request()->routeIs('notifications')"
            :badge="auth()->user()?->unreadNotifications()->count() ?: null">
    {{ __('Notifications') }}
</x-nav-item>

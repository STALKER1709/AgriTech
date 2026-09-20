<div class="flex w-full flex-1 flex-col gap-5">
    {{-- En-tête façon « Tableau de bord d'administration » Stitch --}}
    <div class="flex items-center gap-3">
        <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-stitch-primary/10 text-stitch-primary">
            <flux:icon.chart-bar class="size-5" />
        </span>
        <div>
            <h1 class="text-xl font-bold">{{ __('Administration') }}</h1>
            <p class="text-sm text-stitch-muted">{{ __('Vue d\'ensemble de la plateforme.') }}</p>
        </div>
    </div>

    {{-- Tuiles KPI bento façon back-office : pastille colorée + valeur + libellé --}}
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <a href="{{ route('admin.farmers') }}" wire:navigate class="stitch-card group flex items-start gap-3 p-4 transition hover:shadow-raised">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-stitch-warning-soft text-stitch-warning">
                <flux:icon.clipboard-document-check class="size-5" />
            </span>
            <span>
                <span class="stitch-price block text-2xl">{{ $this->farmersAwaitingValidation() }}</span>
                <span class="block text-sm font-semibold">{{ __('Comptes à valider') }}</span>
                <span class="block text-xs text-stitch-muted">{{ __('Agriculteurs ayant payé leurs frais') }}</span>
            </span>
        </a>

        <a href="{{ route('admin.moderation') }}" wire:navigate class="stitch-card group flex items-start gap-3 p-4 transition hover:shadow-raised">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-stitch-terra-soft text-stitch-terra">
                <flux:icon.scale class="size-5" />
            </span>
            <span>
                <span class="stitch-price block text-2xl">{{ $this->publicationsAwaitingModeration() }}</span>
                <span class="block text-sm font-semibold">{{ __('Publications à modérer') }}</span>
                <span class="block text-xs text-stitch-muted">{{ __('Produits et formations soumis') }}</span>
            </span>
        </a>

        <div class="stitch-card flex items-start gap-3 p-4">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-stitch-gold-soft text-[#8d6b00]">
                <flux:icon.clock class="size-5" />
            </span>
            <span>
                <span class="stitch-price block text-2xl">{{ $this->paymentsAwaitingOutcome() }}</span>
                <span class="block text-sm font-semibold">{{ __('Paiements en attente') }}</span>
                <span class="block text-xs text-stitch-muted">{{ __('Sans réponse de l\'opérateur') }}</span>
            </span>
        </div>

        <div class="stitch-card flex items-start gap-3 p-4">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-stitch-success-soft text-stitch-success">
                <flux:icon.building-storefront class="size-5" />
            </span>
            <span>
                <span class="stitch-price block text-2xl">{{ $this->activeFarmers() }}</span>
                <span class="block text-sm font-semibold">{{ __('Agriculteurs actifs') }}</span>
            </span>
        </div>

        <div class="stitch-card flex items-start gap-3 p-4">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-stitch-primary/10 text-stitch-primary">
                <flux:icon.user-group class="size-5" />
            </span>
            <span>
                <span class="stitch-price block text-2xl">{{ $this->clients() }}</span>
                <span class="block text-sm font-semibold">{{ __('Clients actifs') }}</span>
            </span>
        </div>

        <a href="{{ route('admin.users') }}" wire:navigate class="stitch-card group flex items-start gap-3 p-4 transition hover:shadow-raised">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-stitch-terra-soft text-stitch-terra">
                <flux:icon.user class="size-5" />
            </span>
            <span class="min-w-0 flex-1 self-center">
                <span class="block text-sm font-semibold">{{ __('Gestion des utilisateurs') }}</span>
                <span class="block text-xs text-stitch-muted">{{ __('Suspensions, suppressions, recherche') }}</span>
            </span>
            <flux:icon.chevron-right class="mt-0.5 size-4 shrink-0 self-center text-stitch-muted transition group-hover:translate-x-0.5" />
        </a>
    </div>
</div>

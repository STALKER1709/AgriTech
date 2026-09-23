{{--
    Reproduction de `agritech_admin_tableau_de_bord` : chapeau avec pastille
    d'état, grille de six indicateurs, puis la colonne large — graphique des
    volumes et flux des dernières opérations — face à la colonne d'accès
    rapide.

    Écarts : la maquette propose un filtre par région, un sélecteur de
    période, un bouton « Actualiser » et un menu d'exports (PDF officiel,
    rapport trimestriel, bordereau fiscal). Rien n'exporte, rien n'agrège par
    région, et « actualiser » n'est qu'un rechargement de page. Elle affiche
    aussi un panier moyen, un taux de conversion et un taux de livraison à
    l'heure : les deux premiers se calculent, le troisième supposerait une
    date de livraison promise que rien n'enregistre.
--}}
<div class="flex w-full flex-1 flex-col gap-space-md">
    {{-- Chapeau --}}
    <section class="flex flex-wrap items-center justify-between gap-space-sm">
        <div class="min-w-0">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-primary-fixed text-primary font-label-sm text-label-sm mb-1">
                <span class="w-1.5 h-1.5 rounded-full bg-status-success"></span>
                {{ __('Plateforme active') }}
            </span>
            <h1 class="font-headline-md text-headline-md text-text-primary tracking-tight">
                {{ __('Tableau de bord d\'administration') }}
            </h1>
        </div>

        <span class="font-label-sm text-label-sm text-text-secondary shrink-0">
            {{ __('Au :date', ['date' => now(config('app.timezone'))->translatedFormat('d F Y à H:i')]) }}
        </span>
    </section>

    {{-- Six indicateurs --}}
    <section class="grid grid-cols-2 gap-space-xs lg:grid-cols-3 lg:gap-space-sm">
        @foreach ([
            [
                'icon' => 'how_to_reg', 'tint' => 'warning',
                'label' => __('Agriculteurs à valider'), 'value' => (string) $this->farmersAwaitingValidation(),
                'detail' => __('dossiers en attente'), 'href' => route('admin.farmers'),
            ],
            [
                'icon' => 'gavel', 'tint' => 'error',
                'label' => __('À modérer'), 'value' => (string) $this->publicationsAwaitingModeration(),
                'detail' => __('produits et formations'), 'href' => route('admin.moderation'),
            ],
            [
                'icon' => 'payments', 'tint' => 'primary',
                'label' => __('Encaissé aujourd\'hui'), 'value' => $this->collectedToday()->formatCompact(),
                'detail' => __('FCFA, paiements confirmés'), 'href' => null,
            ],
            [
                'icon' => 'group', 'tint' => 'primary',
                'label' => __('Comptes actifs'), 'value' => (string) ($this->clients() + $this->activeFarmers()),
                'detail' => __(':clients clients, :farmers agriculteurs', [
                    'clients' => $this->clients(), 'farmers' => $this->activeFarmers(),
                ]),
                'href' => route('admin.users'),
            ],
            [
                'icon' => 'sync', 'tint' => 'warning',
                'label' => __('Paiements en vol'), 'value' => (string) $this->paymentsAwaitingOutcome(),
                'detail' => __('en attente d\'une issue'), 'href' => null,
            ],
            [
                'icon' => 'account_balance', 'tint' => 'primary',
                'label' => __('Commission perçue'), 'value' => $this->platformCommission()->formatCompact(),
                'detail' => __('FCFA, sur les sous-commandes payées'), 'href' => null,
            ],
        ] as $kpi)
            <{{ $kpi['href'] ? 'a' : 'div' }}
                @if ($kpi['href']) href="{{ $kpi['href'] }}" wire:navigate @endif
                class="rounded-xl bg-surface-container-lowest p-space-sm shadow-card flex flex-col gap-1 {{ $kpi['href'] ? 'hover:shadow-raised transition-shadow' : '' }}">
                <span @class([
                    'w-9 h-9 rounded-full flex items-center justify-center self-start',
                    'bg-[#fff3e0] text-status-warning' => $kpi['tint'] === 'warning',
                    'bg-[#ffebee] text-status-error' => $kpi['tint'] === 'error',
                    'bg-primary-fixed text-primary' => $kpi['tint'] === 'primary',
                ])>
                    <x-icon :name="$kpi['icon']" size="18" />
                </span>

                <span class="font-label-sm text-label-sm text-text-secondary mt-1">{{ $kpi['label'] }}</span>
                <span class="font-headline-sm text-headline-sm text-text-primary truncate">{{ $kpi['value'] }}</span>
                <span class="font-label-sm text-label-sm text-text-secondary leading-tight">{{ $kpi['detail'] }}</span>
            </{{ $kpi['href'] ? 'a' : 'div' }}>
        @endforeach
    </section>

    <div class="grid gap-space-md xl:grid-cols-3">
        {{-- Colonne large --}}
        <div class="xl:col-span-2 flex flex-col gap-space-md">
            <section class="rounded-2xl bg-surface-container-lowest p-space-md shadow-card">
                <x-stacked-bars :series="$this->monthlyVolume()"
                                :title="__('Volume encaissé')"
                                :subtitle="__('6 derniers mois, en FCFA')"
                                :legend="['orders' => __('Produits'), 'trainings' => __('Formations et Pass')]" />
            </section>

            {{-- Flux d'audit --}}
            <section class="rounded-2xl bg-surface-container-lowest p-space-md shadow-card flex flex-col gap-space-sm">
                <div class="flex items-center justify-between gap-space-sm">
                    <div class="min-w-0">
                        <h2 class="font-headline-sm text-headline-sm text-text-primary">{{ __('Dernières opérations tracées') }}</h2>
                        <p class="font-label-sm text-label-sm text-text-secondary">
                            {{ __('Chaque action administrative sensible laisse une ligne.') }}
                        </p>
                    </div>

                    @can(\App\Models\Privilege::VIEW_AUDIT_LOG)
                        <a href="{{ route('admin.audit') }}" wire:navigate
                           class="font-label-sm text-label-sm text-primary font-semibold hover:underline shrink-0">
                            {{ __('Tout le journal') }}
                        </a>
                    @endcan
                </div>

                <div class="flex flex-col divide-y divide-surface-container">
                    @forelse ($this->recentAudit() as $entry)
                        <div class="flex items-start gap-space-sm py-space-sm">
                            <span class="w-9 h-9 rounded-full bg-surface-container-low text-text-secondary flex items-center justify-center shrink-0">
                                <x-icon name="history" size="18" />
                            </span>

                            <div class="min-w-0 flex-1">
                                <span class="font-body-md-bold text-body-md-bold text-text-primary block truncate">
                                    {{ $entry->action }}
                                </span>
                                <span class="font-label-sm text-label-sm text-text-secondary">
                                    {{ $entry->actor?->name ?? __('Système') }}
                                </span>
                            </div>

                            <time datetime="{{ $entry->created_at->toIso8601String() }}"
                                  class="font-label-sm text-label-sm text-text-secondary shrink-0">
                                {{ $entry->created_at->timezone(config('app.timezone'))->diffForHumans(short: true) }}
                            </time>
                        </div>
                    @empty
                        <p class="font-body-md text-body-md text-text-secondary py-space-sm">
                            {{ __('Aucune action tracée pour l\'instant.') }}
                        </p>
                    @endforelse
                </div>
            </section>
        </div>

        {{-- Colonne d'accès --}}
        <div class="flex flex-col gap-space-md">
            <section class="rounded-2xl bg-surface-container-lowest p-space-md shadow-card flex flex-col gap-space-sm">
                <h2 class="font-headline-sm text-headline-sm text-text-primary">{{ __('Commandes et abonnements') }}</h2>

                @foreach ([
                    ['receipt_long', __('Commandes à payer'), (string) $this->ordersPendingPayment()],
                    ['task_alt', __('Commandes livrées'), (string) $this->ordersDelivered()],
                    ['workspace_premium', __('Abonnements actifs'), (string) $this->activeSubscriptions()],
                    ['payments', __('Volume encaissé'), $this->volumeSettled()->format()],
                ] as [$icon, $label, $value])
                    <div class="flex items-center gap-space-sm">
                        <span class="w-9 h-9 rounded-full bg-surface-container-low text-primary flex items-center justify-center shrink-0">
                            <x-icon :name="$icon" size="18" />
                        </span>
                        <span class="font-body-md text-body-md text-text-secondary flex-1 min-w-0 truncate">{{ $label }}</span>
                        <span class="font-headline-sm text-headline-sm text-text-primary shrink-0">{{ $value }}</span>
                    </div>
                @endforeach
            </section>

            {{-- Raccourcis, chacun derrière son privilège. --}}
            <section class="rounded-2xl bg-surface-container-lowest shadow-card overflow-hidden divide-y divide-surface-container">
                @foreach ([
                    [\App\Models\Privilege::APPROVE_FARMERS, 'how_to_reg', __('Agriculteurs à valider'), 'admin.farmers'],
                    [\App\Models\Privilege::MODERATE_PUBLICATIONS, 'gavel', __('Modération'), 'admin.moderation'],
                    [\App\Models\Privilege::SUSPEND_USERS, 'group', __('Utilisateurs'), 'admin.users'],
                    [\App\Models\Privilege::MANAGE_PRIVILEGES, 'admin_panel_settings', __('Privilèges'), 'admin.privileges'],
                    [\App\Models\Privilege::MANAGE_SETTINGS, 'settings', __('Paramètres'), 'admin.settings'],
                ] as [$privilege, $icon, $label, $route])
                    @can($privilege)
                        <a href="{{ route($route) }}" wire:navigate
                           class="flex items-center gap-space-sm p-space-md hover:bg-surface-container-low transition-colors">
                            <span class="w-9 h-9 rounded-full bg-primary-fixed text-primary flex items-center justify-center shrink-0">
                                <x-icon :name="$icon" size="18" />
                            </span>
                            <span class="font-body-md-bold text-body-md-bold text-text-primary flex-1 min-w-0 truncate">{{ $label }}</span>
                            <x-icon name="chevron_right" size="20" class="text-text-secondary shrink-0" />
                        </a>
                    @endcan
                @endforeach
            </section>
        </div>
    </div>
</div>

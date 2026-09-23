{{--
    Reproduction de `agritech_notifications` : titre et « Tout lire »,
    carrousel de filtres compté, puis le flux groupé par jour avec sa pastille
    de non-lu, son icône teintée et son étiquette.

    Les catégories de la maquette — Commandes, Paiements, Formations, Système —
    deviennent celles que les données savent remplir : Commandes,
    Publications, Messages, Compte. Une notification n'est jamais fabriquée
    pour l'écran : ce sont les lignes que les services ont écrites quand une
    commande a été payée, une publication modérée ou un message envoyé.
--}}
<div class="flex w-full flex-col gap-space-md">
    {{-- Titre --}}
    <section class="flex items-start justify-between gap-space-sm">
        <div class="flex flex-col min-w-0">
            <h1 class="font-headline-lg-mobile text-headline-lg-mobile text-text-primary tracking-tight">
                {{ __('Notifications') }}
            </h1>
            <p class="font-label-sm text-label-sm text-text-secondary mt-0.5">
                {{ __('Ce que la plateforme a eu à vous dire, du plus récent au plus ancien.') }}
            </p>
        </div>

        @if ($this->unreadCount() > 0)
            <button type="button" wire:click="markAllAsRead" data-test="mark-all-read"
                    class="shrink-0 flex items-center gap-1.5 px-3 py-2 rounded-full bg-primary-fixed text-on-primary-fixed hover:bg-primary hover:text-on-primary transition-colors shadow-card">
                <x-icon name="done_all" size="18" />
                <span class="font-label-sm text-label-sm">{{ __('Tout lire') }}</span>
            </button>
        @endif
    </section>

    {{-- Filtres --}}
    @php($counts = $this->counts())
    <div class="-mx-margin lg:mx-0 px-margin lg:px-0 flex items-center gap-2 overflow-x-auto pb-1 no-scrollbar">
        <button type="button" wire:click="$set('category', '')"
                @class([
                    'shrink-0 h-9 px-4 rounded-full font-label-lg text-label-lg shadow-card transition-colors flex items-center gap-1.5',
                    'bg-primary text-on-primary' => $category === '',
                    'bg-surface-container-lowest text-text-secondary hover:text-text-primary' => $category !== '',
                ])>
            <span>{{ __('Toutes') }}</span>
            @if ($this->unreadCount() > 0)
                <span @class([
                    'px-1.5 rounded-full font-label-sm text-[11px]',
                    'bg-on-primary/20 text-on-primary' => $category === '',
                    'bg-surface-container text-text-primary' => $category !== '',
                ])>{{ $this->unreadCount() }}</span>
            @endif
        </button>

        {{-- Une catégorie sans ligne n'a pas de pastille : un filtre qui ne
             peut rien renvoyer n'est pas un filtre. --}}
        @foreach (\App\Support\NotificationPresenter::CATEGORIES as $key => $label)
            @continue($counts[$key] === 0 && $category !== $key)
            @php($current = $category === $key)
            <button type="button" wire:click="$set('category', '{{ $key }}')"
                    @class([
                        'shrink-0 h-9 px-4 rounded-full font-label-lg text-label-lg shadow-card transition-colors flex items-center gap-1.5',
                        'bg-primary text-on-primary' => $current,
                        'bg-surface-container-lowest text-text-secondary hover:text-text-primary' => ! $current,
                    ])>
                <span>{{ __($label) }}</span>
                <span @class([
                    'px-1.5 rounded-full font-label-sm text-[11px]',
                    'bg-on-primary/20 text-on-primary' => $current,
                    'bg-surface-container text-text-primary' => ! $current,
                ])>{{ $counts[$key] }}</span>
            </button>
        @endforeach

        <button type="button" wire:click="$toggle('unreadOnly')"
                @class([
                    'shrink-0 h-9 px-4 rounded-full font-label-lg text-label-lg shadow-card transition-colors flex items-center gap-1.5',
                    'bg-tertiary-fixed-dim text-on-tertiary-fixed' => $unreadOnly,
                    'bg-surface-container-lowest text-text-secondary hover:text-text-primary' => ! $unreadOnly,
                ])>
            <x-icon name="mark_email_unread" size="16" />
            <span>{{ __('Non lues') }}</span>
        </button>
    </div>

    {{-- Flux --}}
    @php($presenter = $this->presenter())
    @php($groups = $this->groups())

    @forelse ($groups as $day => $notifications)
        <section class="flex flex-col gap-space-sm">
            <div class="flex items-center justify-between px-1">
                <span class="font-label-sm text-label-sm uppercase tracking-wider text-text-secondary font-semibold">
                    {{ $day }}
                </span>

                @php($unread = $notifications->whereNull('read_at')->count())
                @if ($unread > 0)
                    <span class="font-label-sm text-[11px] text-primary font-medium flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-primary inline-block"></span>
                        {{ trans_choice(':count non lue|:count non lues', $unread, ['count' => $unread]) }}
                    </span>
                @endif
            </div>

            <div class="flex flex-col gap-space-sm">
                @foreach ($notifications as $notification)
                    @php($line = $presenter->present($notification))
                    @php($isUnread = $notification->read_at === null)

                    <article @class([
                        'relative flex items-start gap-3.5 p-3.5 rounded-xl shadow-card transition-colors',
                        'bg-surface-container-lowest' => $isUnread,
                        'bg-surface-container-lowest/70' => ! $isUnread,
                    ]) data-test="notification">
                        <div class="relative shrink-0 mt-0.5">
                            <span @class([
                                'w-11 h-11 rounded-full flex items-center justify-center shadow-card',
                                'bg-secondary-fixed text-on-secondary-fixed-variant' => $line['tint'] === 'secondary',
                                'bg-tertiary-fixed text-on-tertiary-fixed-variant' => $line['tint'] === 'tertiary',
                                'bg-primary-fixed text-primary' => $line['tint'] === 'primary',
                                'bg-[#ffebee] text-status-error' => $line['tint'] === 'error',
                            ])>
                                <x-icon :name="$line['icon']" size="22" />
                            </span>

                            @if ($isUnread)
                                <span class="absolute -top-0.5 -right-0.5 w-3 h-3 rounded-full bg-primary ring-2 ring-surface"
                                      aria-label="{{ __('Non lue') }}"></span>
                            @endif
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-baseline justify-between gap-2">
                                <h2 class="font-headline-sm text-[15px] leading-snug text-text-primary font-semibold line-clamp-2">
                                    {{ $line['title'] }}
                                </h2>
                                <time datetime="{{ $notification->created_at?->toIso8601String() }}"
                                      @class([
                                          'shrink-0 font-label-sm text-[11px] font-medium',
                                          'text-primary' => $isUnread,
                                          'text-text-secondary' => ! $isUnread,
                                      ])>
                                    {{ $notification->created_at?->timezone(config('app.timezone'))->diffForHumans(short: true) }}
                                </time>
                            </div>

                            @if ($line['body'] !== '')
                                <p class="font-body-md text-[13px] leading-relaxed text-text-secondary mt-1 line-clamp-2">
                                    {{ $line['body'] }}
                                </p>
                            @endif

                            <div class="flex flex-wrap items-center gap-2 mt-2">
                                @if ($line['tag'])
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-surface-container text-text-primary font-label-sm text-[11px]">
                                        <x-icon :name="$line['tag']['icon']" size="13" />
                                        {{ $line['tag']['label'] }}
                                    </span>
                                @endif

                                @if ($line['url'])
                                    <button type="button" wire:click="open('{{ $notification->id }}')"
                                            class="inline-flex items-center gap-1 font-label-sm text-label-sm text-primary font-semibold hover:underline"
                                            data-test="open-notification">
                                        {{ __('Ouvrir') }}
                                        <x-icon name="arrow_forward" size="14" />
                                    </button>
                                @endif

                                @if ($isUnread)
                                    <button type="button" wire:click="markAsRead('{{ $notification->id }}')"
                                            class="inline-flex items-center gap-1 font-label-sm text-label-sm text-text-secondary hover:text-text-primary transition-colors"
                                            data-test="mark-read">
                                        <x-icon name="done" size="14" />
                                        {{ __('Marquer lue') }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @empty
        <div class="rounded-2xl bg-surface-container-lowest flex flex-col items-center gap-space-sm px-space-md py-space-2xl text-center shadow-card">
            <span class="flex w-14 h-14 items-center justify-center rounded-full bg-surface-container-low">
                <x-icon name="notifications" size="28" class="text-text-secondary" />
            </span>
            <h2 class="font-headline-sm text-headline-sm text-text-primary">
                {{ $category !== '' || $unreadOnly ? __('Rien dans ce filtre') : __('Aucune notification') }}
            </h2>
            <p class="font-body-md text-body-md text-text-secondary">
                {{ $category !== '' || $unreadOnly
                    ? __('Essayez une autre catégorie, ou retirez le filtre « non lues ».')
                    : __('Les paiements, les commandes et les messages viendront s\'inscrire ici.') }}
            </p>

            @if ($category !== '' || $unreadOnly)
                <button type="button" wire:click="resetFilters"
                        class="mt-1 h-12 px-6 rounded-full bg-surface-container-low text-primary font-label-lg text-label-lg inline-flex items-center gap-1.5 hover:bg-surface-container-high transition-colors">
                    <x-icon name="filter_alt_off" size="18" />
                    {{ __('Effacer les filtres') }}
                </button>
            @endif
        </div>
    @endforelse
</div>

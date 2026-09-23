{{--
    Reproduction de `agritech_messages` : champ de recherche, pastilles de
    filtre comptées, puis une carte par conversation.

    La maquette propose un fil « Support technique » et une pastille de
    présence : il n'existe ni compte de support ni suivi de connexion. Les
    pastilles disent ce que le serveur sait — tous les fils, et ceux qui
    attendent une réponse.
--}}
<div class="flex w-full flex-1 flex-col gap-space-md">
    <section class="flex flex-col gap-space-sm">
        <div class="flex items-center gap-space-sm min-w-0">
            <span class="flex w-10 h-10 shrink-0 items-center justify-center rounded-full bg-primary-fixed text-primary">
                <x-icon name="forum" size="22" />
            </span>
            <div class="min-w-0">
                <h1 class="font-headline-md text-headline-md text-text-primary tracking-tight">{{ __('Messages') }}</h1>
                <p class="font-label-sm text-label-sm text-text-secondary truncate">
                    {{ __('Vos échanges avec les producteurs, du plus récent au plus ancien.') }}
                </p>
            </div>
        </div>

        <div class="relative flex items-center bg-surface-container-lowest rounded-xl shadow-card">
            <span class="pl-space-md text-text-secondary pointer-events-none">
                <x-icon name="search" size="20" />
            </span>
            <input type="search" wire:model.live.debounce.400ms="search"
                   placeholder="{{ __('Rechercher un correspondant, un message…') }}"
                   class="w-full h-12 bg-transparent pl-space-sm pr-10 font-body-md text-body-md text-text-primary placeholder:text-text-secondary focus:outline-none" />

            @if ($search !== '')
                <button type="button" wire:click="$set('search', '')" aria-label="{{ __('Effacer la recherche') }}"
                        class="absolute right-2 w-8 h-8 rounded-full flex items-center justify-center text-text-secondary hover:text-text-primary hover:bg-surface-container-high transition-colors">
                    <x-icon name="cancel" size="18" />
                </button>
            @endif
        </div>

        <div class="-mx-margin lg:mx-0 px-margin lg:px-0 overflow-x-auto no-scrollbar flex gap-space-xs">
            <button type="button" wire:click="$set('filter', '')"
                    @class([
                        'shrink-0 h-9 px-4 rounded-full font-label-sm text-label-sm flex items-center gap-1.5 shadow-card transition-colors',
                        'bg-primary text-on-primary' => $filter === '',
                        'bg-surface-container-lowest text-text-secondary hover:text-text-primary' => $filter !== '',
                    ])>
                <x-icon name="forum" size="16" />
                {{ __('Tous (:count)', ['count' => $conversations->total()]) }}
            </button>

            <button type="button" wire:click="$set('filter', 'unread')"
                    @class([
                        'shrink-0 h-9 px-4 rounded-full font-label-sm text-label-sm flex items-center gap-1.5 shadow-card transition-colors',
                        'bg-primary text-on-primary' => $filter === 'unread',
                        'bg-surface-container-lowest text-text-secondary hover:text-text-primary' => $filter !== 'unread',
                    ])>
                @if ($this->unreadTotal() > 0)
                    <span class="w-2 h-2 rounded-full bg-status-success"></span>
                @endif
                {{ __('Non lus (:count)', ['count' => $this->unreadTotal()]) }}
            </button>

            @if ($this->activeFilterCount() > 0)
                <button type="button" wire:click="resetFilters"
                        class="shrink-0 h-9 px-4 rounded-full bg-surface-container-lowest text-text-secondary hover:text-text-primary font-label-sm text-label-sm flex items-center gap-1.5 shadow-card transition-colors">
                    <x-icon name="filter_alt_off" size="16" />
                    {{ __('Effacer') }}
                </button>
            @endif
        </div>
    </section>

    <div class="flex flex-col gap-space-sm">
        @forelse ($this->visibleConversations() as $conversation)
            <x-conversation-row :conversation="$conversation"
                                :counterpart="$this->counterpart($conversation)"
                                :reader="auth()->user()"
                                :subtitle="__('Producteur')"
                                :unread="$conversation->unreadCountFor(auth()->user())"
                                :href="route('client.messages.show', ['conversation' => $conversation->id])" />
        @empty
            <div class="rounded-2xl bg-surface-container-lowest flex flex-col items-center gap-space-sm px-space-md py-space-2xl text-center shadow-card">
                <span class="flex w-14 h-14 items-center justify-center rounded-full bg-surface-container-low">
                    <x-icon name="forum" size="28" class="text-text-secondary" />
                </span>
                <h2 class="font-headline-sm text-headline-sm text-text-primary">
                    {{ $this->activeFilterCount() > 0 ? __('Aucun fil ne correspond') : __('Aucune conversation') }}
                </h2>
                <p class="font-body-md text-body-md text-text-secondary max-w-sm">
                    {{ $this->activeFilterCount() > 0
                        ? __('Essayez un autre mot, ou retirez le filtre « non lus ».')
                        : __('Écrivez à un producteur depuis une fiche produit pour démarrer un échange.') }}
                </p>

                @if ($this->activeFilterCount() > 0)
                    <button type="button" wire:click="resetFilters"
                            class="mt-1 h-12 px-6 rounded-full bg-surface-container-low text-primary font-label-lg text-label-lg inline-flex items-center gap-1.5 hover:bg-surface-container-high transition-colors">
                        <x-icon name="filter_alt_off" size="18" />
                        {{ __('Effacer les filtres') }}
                    </button>
                @endif
            </div>
        @endforelse

        @if ($conversations->hasPages())
            <div class="pt-space-xs">{{ $conversations->links() }}</div>
        @endif
    </div>
</div>

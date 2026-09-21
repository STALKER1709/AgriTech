<div class="flex w-full flex-1 flex-col gap-5">
    {{-- En-tête façon écran Stitch « Messages » --}}
    <div class="flex items-center gap-3">
        <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-stitch-primary/10 text-stitch-primary">
            <flux:icon.chat-bubble-left-right class="size-5" />
        </span>
        <div>
            <h1 class="text-xl font-bold">{{ __('Mes messages') }}</h1>
            <p class="text-sm text-stitch-muted">{{ __('Vos échanges avec les agriculteurs.') }}</p>
        </div>
    </div>

    <div class="flex flex-col gap-2.5">
        @forelse ($conversations as $conversation)
            @php
                $farmer = $conversation->farmer;
                $last = $conversation->messages->first();
                $unread = $conversation->unreadCountFor(auth()->user() ?? \Illuminate\Support\Facades\Auth::user());
            @endphp

            <a href="{{ route('client.messages.show', ['conversation' => $conversation->id]) }}"
               class="stitch-card flex items-center gap-3 p-3.5 transition hover:shadow-raised">
                {{-- Avatar initiales façon liste Stitch --}}
                <span class="grid size-11 shrink-0 place-items-center rounded-full bg-stitch-primary/10 font-display text-sm font-bold text-stitch-primary">
                    {{ mb_strtoupper(mb_substr($farmer->farmerProfile?->farm_name ?? $farmer->name, 0, 2)) }}
                </span>

                <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between gap-2">
                        <p class="truncate text-sm font-bold">
                            {{ $farmer->farmerProfile?->farm_name ?? $farmer->name }}
                        </p>
                        @if ($conversation->last_message_at)
                            <span class="shrink-0 text-xs text-stitch-muted">
                                {{ $conversation->last_message_at->timezone(config('app.timezone'))->diffForHumans() }}
                            </span>
                        @endif
                    </div>

                    @if ($last)
                        <p class="mt-0.5 truncate text-sm text-stitch-muted">
                            {{ $last->sender_id === auth()->id() ? __('Vous : ') : '' }}{{ $last->content }}
                        </p>
                    @endif
                </div>

                @if ($unread > 0)
                    <span class="grid h-5 min-w-5 shrink-0 place-items-center rounded-full bg-stitch-terra px-1.5 text-[11px] font-bold leading-none text-white">
                        {{ $unread > 9 ? '9+' : $unread }}
                    </span>
                @endif
            </a>
        @empty
            <div class="stitch-card flex flex-col items-center gap-3 px-6 py-12 text-center">
                <span class="flex size-14 items-center justify-center rounded-full bg-stitch-low">
                    <flux:icon.chat-bubble-left-right class="size-7 text-stitch-muted" />
                </span>
                <h2 class="font-display text-lg font-bold">{{ __('Aucune conversation') }}</h2>
                <p class="max-w-xs text-sm text-stitch-muted">
                    {{ __('Contactez un agriculteur depuis une fiche produit pour démarrer un échange.') }}
                </p>
            </div>
        @endforelse

        {{ $conversations->links() }}
    </div>
</div>

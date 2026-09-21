<div class="flex w-full flex-1 flex-col gap-4">
    {{-- En-tête conversation façon écran Stitch : retour + avatar + nom --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('client.messages') }}" wire:navigate
           class="grid size-10 shrink-0 place-items-center rounded-full border border-stitch-border bg-white shadow-card transition hover:bg-stitch-low"
           aria-label="{{ __('Retour aux messages') }}">
            <flux:icon.arrow-left class="size-5" />
        </a>

        <span class="grid size-10 shrink-0 place-items-center rounded-full bg-stitch-primary/10 font-display text-sm font-bold text-stitch-primary">
            {{ mb_strtoupper(mb_substr($conversation->farmer->farmerProfile?->farm_name ?? $conversation->farmer->name, 0, 2)) }}
        </span>

        <div class="min-w-0">
            <h1 class="truncate text-base font-bold">
                {{ $conversation->farmer->farmerProfile?->farm_name ?? $conversation->farmer->name }}
            </h1>
            <p class="text-xs text-stitch-muted">{{ __('Conversation directe') }}</p>
        </div>
    </div>

    {{-- Fil de messages : bulles stitch (miennes vert forêt, autres blanches) --}}
    <div class="flex flex-col gap-2.5" wire:poll.5s>
        @foreach ($this->messages() as $message)
            @php($mine = $message->sender_id === auth()->id())
            <div class="flex {{ $mine ? 'justify-end' : 'justify-start' }}">
                <div class="max-w-[85%] px-4 py-2.5 shadow-card
                            {{ $mine
                                ? 'rounded-2xl rounded-br-md bg-stitch-primary text-white'
                                : 'rounded-2xl rounded-bl-md border border-stitch-border bg-white text-stitch-ink' }}">
                    <p class="whitespace-pre-line text-sm leading-relaxed">{{ $message->content }}</p>
                    <p class="mt-1 text-right text-[11px] {{ $mine ? 'text-white/70' : 'text-stitch-muted' }}">
                        {{ $message->created_at->timezone(config('app.timezone'))->format('H:i') }}
                        @if ($mine && $message->isRead())
                            · {{ __('lu') }}
                        @endif
                    </p>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Composer : pilule arrondie avec bouton rond, façon écran Stitch --}}
    <form wire:submit="send"
          class="sticky bottom-24 z-30 flex items-end gap-2 rounded-3xl border border-stitch-border bg-white p-2 shadow-raised lg:bottom-6">
        <flux:textarea
            wire:model="content"
            :placeholder="__('Votre message…')"
            rows="1"
            class="flex-1 [&_textarea]:!min-h-11 [&_textarea]:!rounded-full [&_textarea]:!border-none [&_textarea]:bg-stitch-low [&_textarea]:px-4 [&_textarea]:py-2.5 [&_textarea]:focus:!ring-0"
            data-test="message-content"
        />

        <button type="submit"
                wire:loading.attr="disabled"
                data-test="send-message"
                class="grid size-11 shrink-0 place-items-center rounded-full bg-stitch-primary text-white shadow-card transition hover:bg-stitch-primary-dark"
                aria-label="{{ __('Envoyer') }}">
            <span wire:loading.remove wire:target="send">
                <flux:icon.paper-airplane class="size-5" />
            </span>
            <span wire:loading wire:target="send">
                <flux:icon.loading class="size-5 animate-spin" />
            </span>
        </button>
    </form>
</div>

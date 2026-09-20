<div class="flex w-full flex-1 flex-col gap-6">
    <div>
        <flux:heading size="xl" level="1">{{ __('Messages reçus') }}</flux:heading>
        <flux:text class="mt-2">{{ __('Vos échanges avec vos clients.') }}</flux:text>
    </div>

    <div class="flex flex-col gap-2">
        @forelse ($conversations as $conversation)
            @php
                $client = $conversation->client;
                $last = $conversation->messages->first();
                $unread = $conversation->unreadCountFor(auth()->user());
            @endphp

            <a href="{{ route('farmer.messages.show', ['conversation' => $conversation->id]) }}"
               class="flex items-center justify-between gap-3 rounded-xl border border-neutral-200 p-4 transition hover:border-neutral-400 dark:border-neutral-700">
                <div class="min-w-0">
                    <flux:heading size="sm" class="truncate">{{ $client->name }}</flux:heading>

                    @if ($last)
                        <flux:text class="mt-1 block truncate text-sm text-zinc-500">
                            {{ $last->sender_id === auth()->id() ? __('Vous : ') : '' }}{{ $last->content }}
                        </flux:text>
                    @endif
                </div>

                <div class="flex shrink-0 flex-col items-end gap-1">
                    @if ($conversation->last_message_at)
                        <flux:text class="text-xs text-zinc-500">
                            {{ $conversation->last_message_at->timezone(config('app.timezone'))->diffForHumans() }}
                        </flux:text>
                    @endif

                    @if ($unread > 0)
                        <flux:badge size="sm" variant="danger">{{ $unread }}</flux:badge>
                    @endif
                </div>
            </a>
        @empty
            <flux:callout icon="chat-bubble-left-right">
                <flux:callout.heading>{{ __('Aucune conversation') }}</flux:callout.heading>
                <flux:callout.text>{{ __('Vos clients vous écriront depuis vos fiches produits et formations.') }}</flux:callout.text>
            </flux:callout>
        @endforelse

        {{ $conversations->links() }}
    </div>
</div>

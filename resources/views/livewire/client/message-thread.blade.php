<div class="flex w-full flex-1 flex-col gap-6">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item :href="route('client.messages')" wire:navigate>{{ __('Mes messages') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $conversation->farmer->farmerProfile?->farm_name ?? $conversation->farmer->name }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex flex-col gap-3" wire:poll.5s>
        @foreach ($this->messages() as $message)
            <div class="flex {{ $message->sender_id === auth()->id() ? 'justify-end' : 'justify-start' }}">
                <div class="max-w-[80%] rounded-xl border px-4 py-2
                            {{ $message->sender_id === auth()->id()
                                ? 'border-neutral-200 bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-800'
                                : 'border-neutral-200 dark:border-neutral-700' }}">
                    <flux:text class="whitespace-pre-line">{{ $message->content }}</flux:text>
                    <flux:text class="mt-1 block text-right text-xs text-zinc-500">
                        {{ $message->created_at->timezone(config('app.timezone'))->format('H:i') }}
                        @if ($message->sender_id === auth()->id() && $message->isRead())
                            · {{ __('lu') }}
                        @endif
                    </flux:text>
                </div>
            </div>
        @endforeach
    </div>

    <form wire:submit="send" class="flex items-end gap-2">
        <flux:textarea wire:model="content" :label="__('Votre message')" rows="2" class="flex-1" data-test="message-content" />

        <flux:button type="submit" variant="primary" wire:loading.attr="disabled" data-test="send-message">
            <span wire:loading.remove wire:target="send">{{ __('Envoyer') }}</span>
            <span wire:loading wire:target="send">{{ __('Envoi…') }}</span>
        </flux:button>
    </form>
</div>

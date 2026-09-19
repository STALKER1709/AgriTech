<div class="flex w-full flex-1 flex-col gap-6">
    <div>
        <flux:heading size="xl" level="1">{{ __('Journal d\'audit') }}</flux:heading>
        <flux:text class="mt-2">{{ __('Qui a fait quoi, quand, et ce qui a changé.') }}</flux:text>
    </div>

    <flux:select wire:model.live="action" class="sm:max-w-sm">
        <flux:select.option value="">{{ __('Toutes les actions') }}</flux:select.option>
        @foreach ($this->actions() as $code => $label)
            <flux:select.option value="{{ $code }}">{{ $label }}</flux:select.option>
        @endforeach
    </flux:select>

    <div class="flex flex-col gap-2">
        @forelse ($entries as $entry)
            <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                <button type="button" wire:click="toggle({{ $entry->id }})" class="flex w-full flex-col gap-1 text-start">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <flux:heading size="sm">{{ \App\Services\Admin\AuditLogger::label($entry->action) }}</flux:heading>
                        <flux:text class="text-xs">{{ $entry->created_at->translatedFormat('d/m/Y à H:i') }}</flux:text>
                    </div>
                    <flux:text class="text-sm">
                        {{ $entry->actor?->name ?? __('Système') }}
                        @if ($entry->auditable_id)
                            · {{ __('cible n° :id', ['id' => $entry->auditable_id]) }}
                        @endif
                    </flux:text>
                </button>

                @if ($expanded === $entry->id)
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-900">
                            <flux:text class="text-xs font-medium">{{ __('Avant') }}</flux:text>
                            <pre class="mt-1 overflow-x-auto text-xs">{{ json_encode($entry->before, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>
                        <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-900">
                            <flux:text class="text-xs font-medium">{{ __('Après') }}</flux:text>
                            <pre class="mt-1 overflow-x-auto text-xs">{{ json_encode($entry->after, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <flux:callout icon="document-text">
                <flux:callout.text>{{ __('Aucune action enregistrée pour ce filtre.') }}</flux:callout.text>
            </flux:callout>
        @endforelse
    </div>

    {{ $entries->links() }}
</div>

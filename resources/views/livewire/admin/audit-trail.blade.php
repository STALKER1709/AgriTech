{{--
    Reproduction de `agritech_admin_journal_d_audit` : chapeau, filtre par
    action, puis le journal — une ligne dépliable par entrée, avec l'avant et
    l'après côte à côte.

    Écarts : la maquette propose un export réglementaire, une adresse IP et
    un appareil par ligne. Rien n'exporte, et la table d'audit n'enregistre
    ni IP ni agent utilisateur — c'est délibéré : le journal retient qui,
    quoi, quand et ce qui a changé, pas de quoi profiler quelqu'un.
--}}
<div class="flex w-full flex-1 flex-col gap-space-md">
    <x-admin-header icon="receipt_long"
                    :eyebrow="__('Règle RG11 — toute action sensible laisse une trace')"
                    :title="__('Journal d\'audit')"
                    :subtitle="__('Qui a fait quoi, quand, et ce qui a changé.')" />

    <div class="lg:max-w-sm">
        <x-form-field wire="action" type="select" :label="__('Filtrer par action')" icon="filter_alt"
                      :mark-optional="false"
                      :options="$this->actions()"
                      :placeholder="__('Toutes les actions')" />
    </div>

    <div class="flex flex-col gap-space-sm">
        @forelse ($entries as $entry)
            <article class="rounded-2xl bg-surface-container-lowest shadow-card overflow-hidden" data-test="audit-row">
                <button type="button" wire:click="toggle({{ $entry->id }})"
                        aria-expanded="{{ $expanded === $entry->id ? 'true' : 'false' }}"
                        class="w-full text-start p-space-md flex items-start gap-space-sm hover:bg-surface-container-low transition-colors">
                    <span class="w-10 h-10 rounded-full bg-surface-container-low text-text-secondary flex items-center justify-center shrink-0">
                        <x-icon name="history" size="20" />
                    </span>

                    <span class="min-w-0 flex-1">
                        <span class="font-headline-sm text-headline-sm text-text-primary block">
                            {{ \App\Services\Admin\AuditLogger::label($entry->action) }}
                        </span>
                        <span class="font-label-sm text-label-sm text-text-secondary">
                            {{ $entry->actor?->name ?? __('Système') }}
                            @if ($entry->auditable_id)
                                · {{ __('cible n° :id', ['id' => $entry->auditable_id]) }}
                            @endif
                        </span>
                    </span>

                    <span class="flex items-center gap-2 shrink-0">
                        <time datetime="{{ $entry->created_at->toIso8601String() }}"
                              class="font-label-sm text-label-sm text-text-secondary">
                            {{ $entry->created_at->timezone(config('app.timezone'))->translatedFormat('d/m/Y à H:i') }}
                        </time>
                        <x-icon :name="$expanded === $entry->id ? 'expand_less' : 'expand_more'" size="20" class="text-text-secondary" />
                    </span>
                </button>

                @if ($expanded === $entry->id)
                    <div class="px-space-md pb-space-md grid gap-space-sm sm:grid-cols-2">
                        @foreach ([[__('Avant'), $entry->before, 'bg-surface-container-low'], [__('Après'), $entry->after, 'bg-primary-fixed/40']] as [$caption, $payload, $tint])
                            <div class="rounded-xl {{ $tint }} p-space-sm min-w-0">
                                <span class="font-label-lg text-label-lg text-text-secondary block mb-1">{{ $caption }}</span>
                                <pre class="overflow-x-auto font-label-sm text-label-sm text-text-primary whitespace-pre-wrap break-words">{{ $payload === null ? '—' : json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                            </div>
                        @endforeach
                    </div>
                @endif
            </article>
        @empty
            <div class="rounded-2xl bg-surface-container-lowest flex flex-col items-center gap-space-sm px-space-md py-space-2xl text-center shadow-card">
                <span class="flex w-14 h-14 items-center justify-center rounded-full bg-surface-container-low">
                    <x-icon name="history" size="28" class="text-text-secondary" />
                </span>
                <h2 class="font-headline-sm text-headline-sm text-text-primary">{{ __('Aucune action enregistrée') }}</h2>
                <p class="font-body-md text-body-md text-text-secondary max-w-sm">
                    {{ $action !== ''
                        ? __('Rien pour ce filtre. Essayez « Toutes les actions ».')
                        : __('Les approbations, suspensions, modérations et changements de privilèges viendront s\'inscrire ici.') }}
                </p>
            </div>
        @endforelse

        @if ($entries->hasPages())
            <div class="pt-space-xs">{{ $entries->links() }}</div>
        @endif
    </div>
</div>

{{--
    Reproduction de `agritech_admin_utilisateurs` : chapeau, barre de
    recherche et de filtres, puis l'annuaire. Chaque ligne devient une carte
    sous `lg` — un tableau de six colonnes ne se lit pas à 360 px.

    Écarts : la maquette annonce un export CSV de l'annuaire et une création
    de compte depuis le back-office. Rien n'exporte, et un compte se crée par
    inscription — en fabriquer un ici contournerait le mot de passe que son
    titulaire est seul à choisir. Les quatre grands totaux de la maquette
    deviennent les comptes réels par rôle.
--}}
<div class="flex w-full flex-1 flex-col gap-space-md">
    <x-admin-header icon="group"
                    :eyebrow="__('Règle RG07 — chaque action passe par un privilège')"
                    :title="__('Utilisateurs')"
                    :subtitle="__('Rechercher, suspendre, réintégrer ou supprimer un compte.')" />

    {{-- Filtres --}}
    <section class="flex flex-col gap-space-sm lg:flex-row lg:items-end">
        <div class="relative flex items-center bg-surface-container-lowest rounded-xl shadow-card lg:flex-1">
            <span class="pl-space-md text-text-secondary pointer-events-none">
                <x-icon name="search" size="20" />
            </span>
            <input type="search" wire:model.live.debounce.400ms="search"
                   placeholder="{{ __('Nom, e-mail ou téléphone') }}"
                   class="w-full h-12 bg-transparent pl-space-sm pr-4 font-body-md text-body-md text-text-primary placeholder:text-text-secondary focus:outline-none" />
        </div>

        <div class="grid grid-cols-2 gap-space-sm lg:w-[28rem]">
            <x-form-field wire="role" type="select" :label="__('Rôle')" icon="badge" :mark-optional="false"
                          :options="collect($this->roles())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->all()"
                          :placeholder="__('Tous les rôles')" />

            <x-form-field wire="status" type="select" :label="__('Statut')" icon="toggle_on" :mark-optional="false"
                          :options="collect($this->statuses())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->all()"
                          :placeholder="__('Tous les statuts')" />
        </div>
    </section>

    {{-- Annuaire --}}
    <div class="flex flex-col gap-space-sm">
        @forelse ($users as $user)
            @php($tone = match (true) {
                $user->isActive() => ['bg-[#e8f5e9] text-status-success', 'check_circle'],
                $user->status === \App\Enums\UserStatus::Deleted => ['bg-surface-container-high text-text-secondary', 'person_off'],
                $user->status === \App\Enums\UserStatus::Suspended => ['bg-[#ffebee] text-status-error', 'block'],
                default => ['bg-[#fff3e0] text-status-warning', 'hourglass_top'],
            })

            <article class="rounded-2xl bg-surface-container-lowest shadow-card overflow-hidden" data-test="user-row">
                <div class="p-space-md flex flex-wrap items-start justify-between gap-space-sm">
                    <div class="flex items-center gap-space-sm min-w-0">
                        <span class="w-11 h-11 rounded-full bg-primary-fixed flex items-center justify-center font-headline-sm text-[13px] text-primary shrink-0">
                            {{ $user->initials() }}
                        </span>

                        <div class="min-w-0">
                            <span class="font-headline-sm text-headline-sm text-text-primary truncate block">{{ $user->name }}</span>
                            <span class="font-label-sm text-label-sm text-text-secondary truncate block">
                                {{ $user->email ?? __('adresse supprimée') }} · {{ $user->phone ?? __('numéro supprimé') }}
                            </span>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-1.5 shrink-0">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-surface-container text-text-secondary font-label-sm text-label-sm">
                            {{ $user->role->label() }}
                        </span>
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full font-label-sm text-label-sm {{ $tone[0] }}"
                              data-test="user-status">
                            <x-icon :name="$tone[1]" size="14" />
                            {{ $user->status->label() }}
                        </span>
                    </div>
                </div>

                @if ($deleting === $user->id)
                    <div class="mx-space-md mb-space-md flex flex-col gap-space-sm rounded-xl bg-[#ffebee] p-space-md">
                        <p class="font-body-md text-body-md text-status-error leading-relaxed flex items-start gap-2">
                            <x-icon name="report" size="18" class="shrink-0 mt-0.5" />
                            {{ __('La suppression est définitive : le compte est anonymisé — e-mail et téléphone passent à NULL — et ne pourra plus se connecter. Ses commandes et ses paiements restent consultables.') }}
                        </p>

                        <div class="flex flex-wrap gap-space-sm">
                            <button type="button" wire:click="delete" data-test="confirm-delete"
                                    class="h-12 px-5 rounded-full bg-status-error text-on-error font-label-lg text-label-lg inline-flex items-center gap-1.5 shadow-card hover:opacity-90 transition-opacity">
                                <x-icon name="delete_forever" size="18" />
                                {{ __('Supprimer définitivement') }}
                            </button>

                            <button type="button" wire:click="cancelDeletion"
                                    class="h-12 px-5 rounded-full bg-surface-container text-text-primary font-label-lg text-label-lg inline-flex items-center gap-1.5 hover:bg-surface-container-high transition-colors">
                                {{ __('Annuler') }}
                            </button>
                        </div>
                    </div>
                @else
                    @php($canAct = auth()->user()?->can('suspend', $user) || auth()->user()?->can('reinstate', $user) || auth()->user()?->can('delete', $user))

                    @if ($canAct)
                        <div class="px-space-md py-space-sm bg-surface-container-low flex flex-wrap gap-space-sm">
                            @can('suspend', $user)
                                <button type="button" wire:click="suspend({{ $user->id }})" data-test="suspend-user"
                                        class="h-10 px-4 rounded-full bg-surface-container text-text-primary font-label-lg text-label-lg inline-flex items-center gap-1.5 hover:bg-surface-container-high transition-colors">
                                    <x-icon name="block" size="16" />
                                    {{ __('Suspendre') }}
                                </button>
                            @endcan

                            @can('reinstate', $user)
                                <button type="button" wire:click="reinstate({{ $user->id }})" data-test="reinstate-user"
                                        class="h-10 px-4 rounded-full bg-primary text-on-primary font-label-lg text-label-lg inline-flex items-center gap-1.5 shadow-card hover:bg-primary-container transition-colors">
                                    <x-icon name="restart_alt" size="16" />
                                    {{ __('Réintégrer') }}
                                </button>
                            @endcan

                            @can('delete', $user)
                                <button type="button" wire:click="confirmDeletion({{ $user->id }})" data-test="delete-user"
                                        class="h-10 px-4 rounded-full bg-surface-container text-status-error font-label-lg text-label-lg inline-flex items-center gap-1.5 hover:bg-surface-container-high transition-colors">
                                    <x-icon name="delete" size="16" />
                                    {{ __('Supprimer') }}
                                </button>
                            @endcan
                        </div>
                    @endif
                @endif
            </article>
        @empty
            <div class="rounded-2xl bg-surface-container-lowest flex flex-col items-center gap-space-sm px-space-md py-space-2xl text-center shadow-card">
                <span class="flex w-14 h-14 items-center justify-center rounded-full bg-surface-container-low">
                    <x-icon name="search_off" size="28" class="text-text-secondary" />
                </span>
                <h2 class="font-headline-sm text-headline-sm text-text-primary">{{ __('Aucun compte ne correspond') }}</h2>
                <p class="font-body-md text-body-md text-text-secondary max-w-sm">
                    {{ __('Essayez un autre mot, ou retirez un filtre.') }}
                </p>
            </div>
        @endforelse

        @if ($users->hasPages())
            <div class="pt-space-xs">{{ $users->links() }}</div>
        @endif
    </div>
</div>

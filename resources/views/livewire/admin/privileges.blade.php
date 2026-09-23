{{--
    Reproduction de `agritech_admin_privil_ges` : chapeau, puis une carte par
    administrateur — identité, privilèges accordés en pastilles, et la grille
    de cases à cocher qui s'ouvre à la modification.

    Écarts : la maquette propose des rôles prédéfinis (« Super Admin »,
    « Auditeur », « Modérateur ») et un historique de connexions. Il n'existe
    pas de rôle intermédiaire — le catalogue de privilèges est la seule
    source — et aucune connexion n'est journalisée.
--}}
<div class="flex w-full flex-1 flex-col gap-space-md">
    <x-admin-header icon="admin_panel_settings"
                    :eyebrow="__('Règle RG07 — le rôle ouvre, le privilège autorise')"
                    :title="__('Privilèges')"
                    :subtitle="__('Le rôle administrateur ouvre cet espace ; ce sont ces privilèges qui autorisent chaque action.')" />

    <div class="flex flex-col gap-space-md">
        @foreach ($admins as $admin)
            <article class="rounded-2xl bg-surface-container-lowest shadow-card overflow-hidden" data-test="admin-row">
                <div class="p-space-md flex items-start justify-between gap-space-sm">
                    <div class="flex items-center gap-space-sm min-w-0">
                        <span class="w-11 h-11 rounded-full bg-primary-fixed flex items-center justify-center font-headline-sm text-[13px] text-primary shrink-0">
                            {{ $admin->initials() }}
                        </span>

                        <div class="min-w-0">
                            <span class="font-headline-sm text-headline-sm text-text-primary truncate block">{{ $admin->name }}</span>
                            <span class="font-label-sm text-label-sm text-text-secondary truncate block">{{ $admin->email }}</span>
                        </div>
                    </div>

                    @if ($admin->is(auth()->user()))
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-primary-fixed text-on-primary-fixed font-label-sm text-label-sm shrink-0">
                            <x-icon name="person" size="14" />
                            {{ __('Vous') }}
                        </span>
                    @endif
                </div>

                @if ($editing === $admin->id)
                    <form wire:submit="save" class="mx-space-md mb-space-md flex flex-col gap-space-sm rounded-xl bg-surface-container-low p-space-md">
                        <span class="font-label-lg text-label-lg text-text-secondary">{{ __('Privilèges accordés') }}</span>

                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach ($this->catalogue() as $code => $label)
                                <label class="flex items-start gap-2.5 cursor-pointer rounded-xl bg-surface-container-lowest p-space-sm hover:bg-surface-container transition-colors">
                                    <input type="checkbox" value="{{ $code }}" wire:model="selected"
                                           class="w-5 h-5 mt-0.5 rounded-md border-outline-variant text-primary focus:ring-primary shrink-0" />
                                    <span class="min-w-0">
                                        <span class="font-label-lg text-label-lg text-text-primary block">{{ $label }}</span>
                                        <span class="font-label-sm text-label-sm text-text-secondary">{{ $code }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>

                        @error('selected')
                            <p class="flex items-center gap-1 font-label-sm text-label-sm text-status-error">
                                <x-icon name="error" size="14" />
                                {{ $message }}
                            </p>
                        @enderror

                        <div class="flex flex-wrap gap-space-sm">
                            <button type="submit" data-test="save-privileges"
                                    class="h-12 px-5 rounded-full bg-primary text-on-primary font-label-lg text-label-lg inline-flex items-center gap-1.5 shadow-card hover:bg-primary-container transition-colors">
                                <x-icon name="save" size="18" />
                                {{ __('Enregistrer') }}
                            </button>

                            <button type="button" wire:click="cancel"
                                    class="h-12 px-5 rounded-full bg-surface-container text-text-primary font-label-lg text-label-lg inline-flex items-center gap-1.5 hover:bg-surface-container-high transition-colors">
                                {{ __('Annuler') }}
                            </button>
                        </div>
                    </form>
                @else
                    <div class="px-space-md pb-space-sm flex flex-wrap gap-1.5">
                        @forelse ($admin->privileges as $privilege)
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-primary-fixed text-on-primary-fixed-variant font-label-sm text-label-sm">
                                <x-icon name="check" size="14" />
                                {{ $privilege->label }}
                            </span>
                        @empty
                            <span class="font-body-md text-body-md text-text-secondary">{{ __('Aucun privilège accordé.') }}</span>
                        @endforelse
                    </div>

                    <div class="px-space-md py-space-sm bg-surface-container-low">
                        @can('managePrivileges', $admin)
                            <button type="button" wire:click="edit({{ $admin->id }})" data-test="edit-privileges"
                                    class="h-10 px-4 rounded-full bg-surface-container text-text-primary font-label-lg text-label-lg inline-flex items-center gap-1.5 hover:bg-surface-container-high transition-colors">
                                <x-icon name="edit" size="16" />
                                {{ __('Modifier les privilèges') }}
                            </button>
                        @else
                            <p class="font-label-sm text-label-sm text-text-secondary flex items-start gap-1.5">
                                <x-icon name="lock" size="15" class="shrink-0 mt-0.5" />
                                {{ $admin->is(auth()->user())
                                    ? __('Vous ne pouvez pas modifier vos propres privilèges.')
                                    : __('Vous n\'avez pas le privilège de gestion des privilèges.') }}
                            </p>
                        @endcan
                    </div>
                @endif
            </article>
        @endforeach
    </div>
</div>

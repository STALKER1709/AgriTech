<div class="flex w-full flex-1 flex-col gap-5">
    {{-- En-tête façon back-office Stitch : pastille + titre + sous-titre --}}
    <div class="flex items-center gap-3">
        <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-stitch-primary/10 text-stitch-primary">
            <flux:icon.user-group class="size-5" />
        </span>
        <div>
            <h1 class="text-xl font-bold">{{ __('Utilisateurs') }}</h1>
            <p class="text-sm text-stitch-muted">{{ __('Rechercher, suspendre, réintégrer ou supprimer un compte.') }}</p>
        </div>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row">
        <flux:input wire:model.live.debounce.300ms="search" :placeholder="__('Nom, e-mail ou téléphone')" class="sm:flex-1" />

        <flux:select wire:model.live="role" :placeholder="__('Tous les rôles')">
            <flux:select.option value="">{{ __('Tous les rôles') }}</flux:select.option>
            @foreach ($this->roles() as $role)
                <flux:select.option value="{{ $role->value }}">{{ $role->label() }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="status" :placeholder="__('Tous les statuts')">
            <flux:select.option value="">{{ __('Tous les statuts') }}</flux:select.option>
            @foreach ($this->statuses() as $status)
                <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="flex flex-col gap-3">        @forelse ($users as $user)
            <div class="stitch-card flex flex-col gap-3 p-4">
                {{-- Liseré de statut à gauche, façon lignes du back-office Stitch --}}
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div class="min-w-0">
                        <flux:heading>{{ $user->name }}</flux:heading>
                        <flux:text class="mt-1 truncate text-sm">
                            {{ $user->email ?? __('adresse supprimée') }} · {{ $user->phone ?? __('numéro supprimé') }}
                        </flux:text>
                    </div>
                    <div class="flex flex-wrap gap-1">
                        <span class="stitch-badge-warning">{{ $user->role->label() }}</span>
                        <span class="{{ $user->isActive() ? 'stitch-badge-success' : 'stitch-badge-danger' }}">{{ $user->status->label() }}</span>
                    </div>
                </div>

                @if ($deleting === $user->id)
                    <div class="flex flex-col gap-3 rounded-xl bg-stitch-danger-soft p-3">
                        <p class="text-sm font-medium text-stitch-danger">
                            {{ __('La suppression est définitive : le compte est anonymisé et ne pourra plus se connecter. Ses commandes et paiements restent consultables.') }}
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <flux:button variant="danger" wire:click="delete" data-test="confirm-delete">
                                {{ __('Supprimer définitivement') }}
                            </flux:button>
                            <flux:button variant="ghost" wire:click="cancelDeletion">{{ __('Annuler') }}</flux:button>
                        </div>
                    </div>
                @else
                    <div class="flex flex-wrap gap-2">
                        @can('suspend', $user)
                            <flux:button size="sm" wire:click="suspend({{ $user->id }})" data-test="suspend-user">
                                {{ __('Suspendre') }}
                            </flux:button>
                        @endcan

                        @can('reinstate', $user)
                            <flux:button size="sm" wire:click="reinstate({{ $user->id }})" data-test="reinstate-user">
                                {{ __('Réintégrer') }}
                            </flux:button>
                        @endcan

                        @can('delete', $user)
                            <flux:button size="sm" variant="danger" wire:click="confirmDeletion({{ $user->id }})" data-test="delete-user">
                                {{ __('Supprimer') }}
                            </flux:button>
                        @endcan
                    </div>
                @endif
            </div>
        @empty
            <flux:callout icon="magnifying-glass">
                <flux:callout.text>{{ __('Aucun utilisateur ne correspond à cette recherche.') }}</flux:callout.text>
            </flux:callout>
        @endforelse
    </div>

    {{ $users->links() }}
</div>

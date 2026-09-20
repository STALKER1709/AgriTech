<div class="flex w-full flex-1 flex-col gap-5">
    {{-- En-tête façon « Gouvernance & Sécurité IAM » Stitch --}}
    <div class="flex items-center gap-3">
        <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-stitch-primary/10 text-stitch-primary">
            <flux:icon.shield-check class="size-5" />
        </span>
        <div>
            <h1 class="text-xl font-bold">{{ __('Privilèges') }}</h1>
            <p class="text-sm text-stitch-muted">
                {{ __('Le rôle administrateur ouvre cet espace ; ce sont ces privilèges qui autorisent chaque action.') }}
            </p>
        </div>
    </div>

    @foreach ($admins as $admin)
        <div class="stitch-card flex flex-col gap-3 p-4">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div class="min-w-0">
                    <flux:heading>{{ $admin->name }}</flux:heading>
                    <flux:text class="mt-1 truncate text-sm">{{ $admin->email }}</flux:text>
                </div>
                @if ($admin->is(auth()->user()))
                    <span class="stitch-badge-success">{{ __('Vous') }}</span>
                @endif
            </div>

            @if ($editing === $admin->id)
                <form wire:submit="save" class="flex flex-col gap-3">
                    <flux:checkbox.group wire:model="selected" :label="__('Privilèges accordés')">
                        @foreach ($this->catalogue() as $code => $label)
                            <flux:checkbox value="{{ $code }}" :label="$label" />
                        @endforeach
                    </flux:checkbox.group>

                    <div class="flex flex-wrap gap-2">
                        <flux:button variant="primary" type="submit" data-test="save-privileges">{{ __('Enregistrer') }}</flux:button>
                        <flux:button variant="ghost" type="button" wire:click="cancel">{{ __('Annuler') }}</flux:button>
                    </div>
                </form>
            @else
                <div class="flex flex-wrap gap-1">
                    @forelse ($admin->privileges as $privilege)
                        <span class="stitch-badge-warning">{{ $privilege->label }}</span>
                    @empty
                        <flux:text class="text-sm">{{ __('Aucun privilège accordé.') }}</flux:text>
                    @endforelse
                </div>

                @can('managePrivileges', $admin)
                    <div>
                        <flux:button size="sm" wire:click="edit({{ $admin->id }})" data-test="edit-privileges">
                            {{ __('Modifier') }}
                        </flux:button>
                    </div>
                @else
                    <flux:text class="text-xs">
                        {{ $admin->is(auth()->user())
                            ? __('Vous ne pouvez pas modifier vos propres privilèges.')
                            : __('Vous n\'avez pas le privilège de gestion des privilèges.') }}
                    </flux:text>
                @endcan
            @endif
        </div>
    @endforeach
</div>

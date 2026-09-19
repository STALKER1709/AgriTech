<div class="flex w-full flex-1 flex-col gap-6">
    <div>
        <flux:heading size="xl" level="1">{{ __('Privilèges') }}</flux:heading>
        <flux:text class="mt-2">
            {{ __('Le rôle administrateur ouvre cet espace ; ce sont ces privilèges qui autorisent chaque action.') }}
        </flux:text>
    </div>

    @foreach ($admins as $admin)
        <div class="flex flex-col gap-3 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div class="min-w-0">
                    <flux:heading>{{ $admin->name }}</flux:heading>
                    <flux:text class="mt-1 truncate text-sm">{{ $admin->email }}</flux:text>
                </div>
                @if ($admin->is(auth()->user()))
                    <flux:badge>{{ __('Vous') }}</flux:badge>
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
                        <flux:badge size="sm">{{ $privilege->label }}</flux:badge>
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

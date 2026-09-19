<div class="flex w-full flex-1 flex-col gap-6">
    <div>
        <flux:heading size="xl" level="1">{{ __('Comptes agriculteurs à valider') }}</flux:heading>
        <flux:text class="mt-2">
            {{ __('Ces agriculteurs ont réglé leurs frais d\'inscription et attendent une décision.') }}
        </flux:text>
    </div>

    @forelse ($farmers as $farmer)
        <div class="flex flex-col gap-3 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <flux:heading size="lg">{{ $farmer->farmerProfile?->farm_name ?? __('Exploitation non renseignée') }}</flux:heading>
                    <flux:text class="mt-1">{{ $farmer->name }}</flux:text>
                </div>
                <flux:badge>{{ $farmer->status->label() }}</flux:badge>
            </div>

            <dl class="grid gap-1 text-sm sm:grid-cols-2">
                <div class="flex gap-2">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Région') }}</dt>
                    <dd>{{ $farmer->farmerProfile?->region ?? '—' }}</dd>
                </div>
                <div class="flex gap-2">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Ville') }}</dt>
                    <dd>{{ $farmer->farmerProfile?->city ?? '—' }}</dd>
                </div>
                <div class="flex gap-2">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('E-mail') }}</dt>
                    <dd class="truncate">{{ $farmer->email }}</dd>
                </div>
                <div class="flex gap-2">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Téléphone') }}</dt>
                    <dd>{{ $farmer->phone }}</dd>
                </div>
            </dl>

            @if ($farmer->farmerProfile?->description)
                <flux:text class="text-sm">{{ $farmer->farmerProfile->description }}</flux:text>
            @endif

            @if ($rejecting === $farmer->id)
                <form wire:submit="reject" class="flex flex-col gap-3 rounded-lg bg-zinc-50 p-3 dark:bg-zinc-900">
                    <flux:textarea
                        wire:model="reason"
                        :label="__('Motif du refus')"
                        :description="__('Ce motif est envoyé à l\'agriculteur et s\'affiche sur son écran de statut.')"
                        rows="3"
                        required
                    />
                    <div class="flex flex-wrap gap-2">
                        <flux:button variant="danger" type="submit" data-test="confirm-rejection">
                            {{ __('Confirmer le refus') }}
                        </flux:button>
                        <flux:button variant="ghost" type="button" wire:click="cancelRejection">
                            {{ __('Annuler') }}
                        </flux:button>
                    </div>
                </form>
            @else
                <div class="flex flex-wrap gap-2">
                    <flux:button variant="primary" wire:click="approve({{ $farmer->id }})" data-test="approve-farmer">
                        {{ __('Approuver') }}
                    </flux:button>
                    <flux:button variant="danger" wire:click="startRejection({{ $farmer->id }})" data-test="start-rejection">
                        {{ __('Refuser') }}
                    </flux:button>
                </div>
            @endif
        </div>
    @empty
        <flux:callout icon="check-circle">
            <flux:callout.text>{{ __('Aucun compte en attente de validation.') }}</flux:callout.text>
        </flux:callout>
    @endforelse

    {{ $farmers->links() }}
</div>

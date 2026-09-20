<div class="flex w-full flex-1 flex-col gap-5">
    {{-- En-tête façon écran « Validation des dossiers agriculteurs » Stitch --}}
    <div class="flex items-center gap-3">
        <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-stitch-warning-soft text-stitch-warning">
            <flux:icon.clipboard-document-check class="size-5" />
        </span>
        <div>
            <h1 class="text-xl font-bold">{{ __('Comptes agriculteurs à valider') }}</h1>
            <p class="text-sm text-stitch-muted">
                {{ __('Ces agriculteurs ont réglé leurs frais d\'inscription et attendent une décision.') }}
            </p>
        </div>
    </div>    @forelse ($farmers as $farmer)
        <div class="stitch-card flex flex-col gap-3 p-4">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div class="min-w-0">
                    <flux:heading size="lg">{{ $farmer->farmerProfile?->farm_name ?? __('Exploitation non renseignée') }}</flux:heading>
                    <flux:text class="mt-1">{{ $farmer->name }}</flux:text>
                </div>
                <span class="stitch-badge-warning">{{ $farmer->status->label() }}</span>
            </div>

            <dl class="grid gap-1 text-sm sm:grid-cols-2">
                <div class="flex gap-2">
                    <dt class="text-stitch-muted ">{{ __('Région') }}</dt>
                    <dd>{{ $farmer->farmerProfile?->region ?? '—' }}</dd>
                </div>
                <div class="flex gap-2">
                    <dt class="text-stitch-muted ">{{ __('Ville') }}</dt>
                    <dd>{{ $farmer->farmerProfile?->city ?? '—' }}</dd>
                </div>
                <div class="flex gap-2">
                    <dt class="text-stitch-muted ">{{ __('E-mail') }}</dt>
                    <dd class="truncate">{{ $farmer->email }}</dd>
                </div>
                <div class="flex gap-2">
                    <dt class="text-stitch-muted ">{{ __('Téléphone') }}</dt>
                    <dd>{{ $farmer->phone }}</dd>
                </div>
            </dl>

            @if ($farmer->farmerProfile?->description)
                <flux:text class="text-sm">{{ $farmer->farmerProfile->description }}</flux:text>
            @endif            @if ($rejecting === $farmer->id)
                <form wire:submit="reject" class="flex flex-col gap-3 rounded-xl border border-stitch-danger/30 bg-stitch-danger-soft p-3">
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

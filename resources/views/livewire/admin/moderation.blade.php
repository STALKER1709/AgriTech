<div class="flex w-full flex-1 flex-col gap-5">
    {{-- En-tête façon « File de modération des contenus » Stitch --}}
    <div class="flex items-center gap-3">
        <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-stitch-terra-soft text-stitch-terra">
            <flux:icon.scale class="size-5" />
        </span>
        <div>
            <h1 class="text-xl font-bold">{{ __('Publications à modérer') }}</h1>
            <p class="text-sm text-stitch-muted">{{ __('Produits et formations soumis par les agriculteurs.') }}</p>
        </div>
    </div>

    @php($total = $products->count() + $trainings->count())

    @if ($total === 0)
        <div class="flex items-center gap-3 rounded-xl border border-stitch-border bg-stitch-success-soft px-4 py-3">
            <flux:icon.check-circle class="size-5 shrink-0 text-stitch-success" />
            <p class="text-sm font-medium text-stitch-success">{{ __('Aucune publication en attente.') }}</p>
        </div>
    @endif

    @foreach ([['product', $products], ['training', $trainings]] as [$type, $items])        @foreach ($items as $item)
            <div class="stitch-card flex flex-col gap-3 p-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="flex min-w-0 gap-3">
                        @if ($type === 'product')
                            <div class="size-16 shrink-0 overflow-hidden rounded-lg bg-stitch-container ">
                                @if ($item->images->isNotEmpty())
                                    <img src="{{ $item->images->first()->url() }}" alt="" class="size-full object-cover" />
                                @endif
                            </div>
                        @endif

                        <div class="min-w-0">
                            <flux:heading class="truncate">
                                {{ $type === 'product' ? $item->name : $item->title }}
                            </flux:heading>
                            <flux:text class="mt-1 text-sm">
                                {{ $item->farmer->farmerProfile?->farm_name ?? $item->farmer->name }}
                                @if ($type === 'product')
                                    · {{ $item->category->name }} · {{ $item->unit_price->format() }}
                                @else
                                    · {{ $item->price->format() }}
                                @endif
                            </flux:text>
                        </div>
                    </div>

                    <span class="{{ $type === 'product' ? 'stitch-badge-success' : 'stitch-badge-warning' }}">
                    {{ $type === 'product' ? __('Produit') : __('Formation') }}
                </span>
                </div>

                <flux:text class="whitespace-pre-line text-sm">{{ Str::limit($item->description, 400) }}</flux:text>                @if ($rejectingType === $type && $rejectingId === $item->id)
                    <form wire:submit="reject" class="flex flex-col gap-3 rounded-xl border border-stitch-danger/30 bg-stitch-danger-soft p-3">
                        <flux:textarea
                            wire:model="reason"
                            :label="__('Motif du refus')"
                            :description="__('Ce motif s\'affiche sur la fiche de l\'agriculteur et lui est envoyé par e-mail.')"
                            rows="3"
                            required
                        />
                        <div class="flex flex-wrap gap-2">
                            <flux:button variant="danger" type="submit" data-test="confirm-publication-rejection">
                                {{ __('Confirmer le refus') }}
                            </flux:button>
                            <flux:button variant="ghost" type="button" wire:click="cancelRejection">{{ __('Annuler') }}</flux:button>
                        </div>
                    </form>
                @else
                    <div class="flex flex-wrap gap-2">
                        <flux:button variant="primary" size="sm"
                                     wire:click="approve('{{ $type }}', {{ $item->id }})"
                                     data-test="approve-publication">
                            {{ __('Approuver') }}
                        </flux:button>
                        <flux:button variant="danger" size="sm"
                                     wire:click="startRejection('{{ $type }}', {{ $item->id }})"
                                     data-test="start-publication-rejection">
                            {{ __('Refuser') }}
                        </flux:button>
                    </div>
                @endif
            </div>
        @endforeach
    @endforeach
</div>

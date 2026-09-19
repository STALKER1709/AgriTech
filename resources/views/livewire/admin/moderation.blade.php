<div class="flex w-full flex-1 flex-col gap-6">
    <div>
        <flux:heading size="xl" level="1">{{ __('Publications à modérer') }}</flux:heading>
        <flux:text class="mt-2">{{ __('Produits et formations soumis par les agriculteurs.') }}</flux:text>
    </div>

    @php($total = $products->count() + $trainings->count())

    @if ($total === 0)
        <flux:callout icon="check-circle">
            <flux:callout.text>{{ __('Aucune publication en attente.') }}</flux:callout.text>
        </flux:callout>
    @endif

    @foreach ([['product', $products], ['training', $trainings]] as [$type, $items])
        @foreach ($items as $item)
            <div class="flex flex-col gap-3 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="flex min-w-0 gap-3">
                        @if ($type === 'product')
                            <div class="size-16 shrink-0 overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800">
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

                    <flux:badge>{{ $type === 'product' ? __('Produit') : __('Formation') }}</flux:badge>
                </div>

                <flux:text class="whitespace-pre-line text-sm">{{ Str::limit($item->description, 400) }}</flux:text>

                @if ($rejectingType === $type && $rejectingId === $item->id)
                    <form wire:submit="reject" class="flex flex-col gap-3 rounded-lg bg-zinc-50 p-3 dark:bg-zinc-900">
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

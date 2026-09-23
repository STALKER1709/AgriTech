{{--
    Reproduction de `agritech_admin_mod_ration` : chapeau compté, puis la
    file — une carte par publication, avec sa vignette, son auteur, sa
    description et les deux décisions.

    Écarts : la maquette propose un score de conformité automatique, des
    filtres par motif de signalement et une modération par lot. Rien n'évalue
    une fiche, aucun signalement n'est collecté, et approuver en masse
    ferait de RG09 une formalité — chaque publication se lit avant de passer.
--}}
@php($total = $products->count() + $trainings->count())

<div class="flex w-full flex-1 flex-col gap-space-md">
    <x-admin-header icon="gavel"
                    :eyebrow="__('Règle RG09 — rien ne se publie sans passage')"
                    :title="__('Publications à modérer')"
                    :subtitle="trans_choice(':count publication en attente|:count publications en attente', $total, ['count' => $total])" />

    @if ($total === 0)
        <div class="rounded-2xl bg-surface-container-lowest flex flex-col items-center gap-space-sm px-space-md py-space-2xl text-center shadow-card">
            <span class="flex w-14 h-14 items-center justify-center rounded-full bg-[#e8f5e9]">
                <x-icon name="task_alt" size="28" class="text-status-success" />
            </span>
            <h2 class="font-headline-sm text-headline-sm text-text-primary">{{ __('La file est vide') }}</h2>
            <p class="font-body-md text-body-md text-text-secondary max-w-sm">
                {{ __('Les nouvelles publications arrivent ici dès qu\'un agriculteur les soumet.') }}
            </p>
        </div>
    @endif

    @foreach ([['product', $products, __('Produit'), 'inventory_2'], ['training', $trainings, __('Formation'), 'school']] as [$type, $items, $kind, $kindIcon])
        @foreach ($items as $item)
            <article class="rounded-2xl bg-surface-container-lowest shadow-card overflow-hidden" data-test="moderation-row">
                <div class="p-space-md flex gap-space-sm">
                    <div class="w-20 h-20 rounded-xl overflow-hidden bg-surface-container shrink-0">
                        @if ($type === 'product' && $item->images->isNotEmpty())
                            <img src="{{ $item->images->first()->url() }}" alt="" loading="lazy" class="w-full h-full object-cover" />
                        @elseif ($type === 'training' && $item->hasCover())
                            <img src="{{ $item->coverUrl() }}" alt="" loading="lazy" class="w-full h-full object-cover" />
                        @else
                            <div class="flex h-full w-full items-center justify-center text-surface-container-highest">
                                <x-icon :name="$kindIcon" size="24" />
                            </div>
                        @endif
                    </div>

                    <div class="min-w-0 flex-1 flex flex-col gap-1">
                        <div class="flex items-start justify-between gap-space-xs">
                            <h2 class="font-headline-sm text-headline-sm text-text-primary line-clamp-2">
                                {{ $type === 'product' ? $item->name : $item->title }}
                            </h2>

                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-surface-container text-text-secondary font-label-sm text-label-sm shrink-0">
                                <x-icon :name="$kindIcon" size="14" />
                                {{ $kind }}
                            </span>
                        </div>

                        <span class="font-label-sm text-label-sm text-text-secondary flex items-center gap-1 truncate">
                            <x-icon name="agriculture" size="14" class="text-secondary shrink-0" />
                            {{ $item->farmer->farmerProfile?->farm_name ?? $item->farmer->name }}
                        </span>

                        <div class="flex flex-wrap items-baseline gap-x-2">
                            <span class="font-price-tag text-price-tag text-primary">
                                {{ ($type === 'product' ? $item->unit_price : $item->price)->format() }}
                            </span>
                            @if ($type === 'product')
                                <span class="font-label-sm text-label-sm text-text-secondary">/ {{ $item->unit->shortLabel() }} · {{ $item->category->name }}</span>
                            @else
                                <span class="font-label-sm text-label-sm text-text-secondary">· {{ $item->format->label() }}</span>
                            @endif
                        </div>

                        <span class="font-label-sm text-label-sm text-text-secondary">
                            {{ __('Soumise le :date', [
                                'date' => $item->updated_at?->timezone(config('app.timezone'))->translatedFormat('d F Y à H:i'),
                            ]) }}
                        </span>
                    </div>
                </div>

                <p class="px-space-md pb-space-md font-body-md text-body-md text-text-secondary leading-relaxed whitespace-pre-line">
                    {{ Str::limit($item->description, 400) }}
                </p>

                @if ($rejectingType === $type && $rejectingId === $item->id)
                    <form wire:submit="reject" class="mx-space-md mb-space-md flex flex-col gap-space-sm rounded-xl bg-[#ffebee] p-space-md">
                        <x-form-field wire="reason" type="textarea" :label="__('Motif du refus')" icon="report" required
                                      :rows="3"
                                      :placeholder="__('Ce que l\'agriculteur doit corriger.')"
                                      :hint="__('Ce motif s\'affiche sur sa fiche, dans son espace.')" />

                        <div class="flex flex-wrap gap-space-sm">
                            <button type="submit" data-test="confirm-publication-rejection"
                                    class="h-12 px-5 rounded-full bg-status-error text-on-error font-label-lg text-label-lg inline-flex items-center gap-1.5 shadow-card hover:opacity-90 transition-opacity">
                                <x-icon name="block" size="18" />
                                {{ __('Confirmer le refus') }}
                            </button>

                            <button type="button" wire:click="cancelRejection"
                                    class="h-12 px-5 rounded-full bg-surface-container text-text-primary font-label-lg text-label-lg inline-flex items-center gap-1.5 hover:bg-surface-container-high transition-colors">
                                {{ __('Annuler') }}
                            </button>
                        </div>
                    </form>
                @else
                    <div class="px-space-md py-space-sm bg-surface-container-low flex flex-wrap gap-space-sm">
                        <button type="button" wire:click="approve('{{ $type }}', {{ $item->id }})" data-test="approve-publication"
                                class="h-11 px-5 rounded-full bg-primary text-on-primary font-label-lg text-label-lg inline-flex items-center gap-1.5 shadow-card hover:bg-primary-container transition-colors">
                            <x-icon name="check_circle" size="18" />
                            {{ __('Publier') }}
                        </button>

                        <button type="button" wire:click="startRejection('{{ $type }}', {{ $item->id }})" data-test="start-publication-rejection"
                                class="h-11 px-5 rounded-full bg-surface-container text-status-error font-label-lg text-label-lg inline-flex items-center gap-1.5 hover:bg-surface-container-high transition-colors">
                            <x-icon name="block" size="18" />
                            {{ __('Refuser') }}
                        </button>
                    </div>
                @endif
            </article>
        @endforeach
    @endforeach
</div>

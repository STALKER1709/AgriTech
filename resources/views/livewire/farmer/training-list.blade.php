{{--
    Reproduction de `agritech_mes_formations_vendeur` : bandeau d'accueil,
    rangée de chiffres, action principale, pastilles de statut, puis une
    carte par formation avec sa couverture et ses actions.

    Écarts : la maquette annonce « Revenus T3 », une note de 4,9 sur 38 avis
    et un onglet « Questions des élèves ». Rien ne découpe les revenus en
    trimestres, aucun avis n'est collecté, et les questions passent par la
    messagerie, qui a déjà son écran. Le montant encaissé et le nombre
    d'inscrits, eux, se comptent.
--}}
@php($counts = $this->counts())
@php($earnings = $this->earnings())

<div class="flex w-full flex-1 flex-col gap-space-md">
    {{-- Bandeau --}}
    <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-primary via-[#165a31] to-[#0d3f20] text-on-primary p-space-md shadow-raised flex flex-col gap-space-md">
        <span class="absolute -right-10 -bottom-10 w-40 h-40 rounded-full bg-tertiary-fixed/10 pointer-events-none" aria-hidden="true"></span>

        <div class="relative z-10">
            <h1 class="font-headline-md text-headline-md text-on-primary tracking-tight">{{ __('Mes formations') }}</h1>
            <p class="font-label-sm text-label-sm text-on-primary/85">
                {{ __('Transmettez ce que vous savez faire, et vendez-le.') }}
            </p>
        </div>

        <div class="relative z-10 grid grid-cols-2 gap-space-xs">
            @foreach ([
                ['payments', $earnings['collected']->format(), __('encaissé, sans retenue')],
                ['groups', (string) $earnings['buyers'], trans_choice(':count inscrit|:count inscrits', $earnings['buyers'], ['count' => $earnings['buyers']])],
            ] as [$icon, $value, $detail])
                <div class="rounded-xl bg-on-primary/15 p-space-sm flex flex-col items-start gap-0.5">
                    <x-icon :name="$icon" size="18" class="text-tertiary-fixed" />
                    <span class="font-headline-sm text-headline-sm text-on-primary truncate">{{ $value }}</span>
                    <span class="font-label-sm text-label-sm text-on-primary/80 leading-tight">{{ $detail }}</span>
                </div>
            @endforeach
        </div>

        <a href="{{ route('farmer.trainings.create') }}" wire:navigate data-test="new-training"
           class="relative z-10 h-12 rounded-full bg-secondary-fixed text-on-secondary-fixed-variant font-label-lg text-label-lg flex items-center justify-center gap-2 shadow-card hover:bg-secondary-fixed-dim transition-colors">
            <x-icon name="add_circle" size="20" />
            {{ __('Proposer une nouvelle formation') }}
        </a>
    </section>

    {{-- Pastilles de statut --}}
    <div class="-mx-margin lg:mx-0 px-margin lg:px-0 overflow-x-auto no-scrollbar flex items-center gap-2">
        <button type="button" wire:click="$set('status', '')"
                @class([
                    'shrink-0 h-9 px-4 rounded-full font-label-sm text-label-sm flex items-center gap-1.5 shadow-card transition-colors',
                    'bg-primary text-on-primary' => $status === '',
                    'bg-surface-container-lowest text-text-secondary hover:text-text-primary' => $status !== '',
                ])>
            <x-icon name="video_library" size="16" />
            {{ __('Toutes') }}
            <span @class([
                'px-1.5 rounded-full text-[11px] font-bold',
                'bg-on-primary/20' => $status === '',
                'bg-surface-container text-text-primary' => $status !== '',
            ])>{{ $counts['all'] }}</span>
        </button>

        @foreach ($this->statuses() as $statusOption)
            @continue($counts[$statusOption->value] === 0 && $status !== $statusOption->value)
            @php($current = $status === $statusOption->value)
            <button type="button" wire:click="$set('status', '{{ $statusOption->value }}')"
                    @class([
                        'shrink-0 h-9 px-4 rounded-full font-label-sm text-label-sm flex items-center gap-1.5 shadow-card transition-colors',
                        'bg-primary text-on-primary' => $current,
                        'bg-surface-container-lowest text-text-secondary hover:text-text-primary' => ! $current,
                    ])>
                {{ $statusOption->label() }}
                <span @class([
                    'px-1.5 rounded-full text-[11px] font-bold',
                    'bg-on-primary/20' => $current,
                    'bg-surface-container text-text-primary' => ! $current,
                ])>{{ $counts[$statusOption->value] }}</span>
            </button>
        @endforeach
    </div>

    {{-- Formations --}}
    <div class="flex flex-col gap-space-md">
        @forelse ($trainings as $training)
            @php($badge = match ($training->status) {
                \App\Enums\PublicationStatus::Published => ['bg-[#e8f5e9] text-status-success', 'check_circle'],
                \App\Enums\PublicationStatus::Rejected => ['bg-[#ffebee] text-status-error', 'cancel'],
                \App\Enums\PublicationStatus::InReview => ['bg-[#fff3e0] text-status-warning', 'hourglass_top'],
                \App\Enums\PublicationStatus::Archived => ['bg-surface-container-high text-text-secondary', 'inventory_2'],
                \App\Enums\PublicationStatus::Draft => ['bg-surface-container text-text-secondary', 'edit_note'],
            })

            <article class="rounded-2xl bg-surface-container-lowest shadow-card overflow-hidden flex flex-col" data-test="training-row">
                <div class="relative w-full aspect-[16/7] bg-surface-container">
                    @if ($training->hasCover())
                        <img src="{{ $training->coverUrl() }}" alt="" loading="lazy" class="w-full h-full object-cover" />
                    @else
                        <div class="flex h-full w-full items-center justify-center text-surface-container-highest">
                            <x-icon name="school" size="36" />
                        </div>
                    @endif

                    <div class="absolute inset-0 bg-gradient-to-t from-inverse-surface/70 via-transparent to-transparent"></div>

                    <span class="absolute top-3 left-3 inline-flex items-center gap-1 px-2.5 py-1 rounded-full font-label-sm text-label-sm {{ $badge[0] }}"
                          data-test="training-status">
                        <x-icon :name="$badge[1]" size="14" />
                        {{ $training->status->label() }}
                    </span>

                    <span class="absolute bottom-3 right-3 inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-inverse-surface/80 backdrop-blur-md text-on-primary font-label-sm text-[11px]">
                        <x-icon :name="$training->format === \App\Enums\TrainingFormat::Pdf ? 'description' : 'play_circle'" size="13" />
                        {{ $training->format->label() }}
                    </span>
                </div>

                <div class="p-space-md flex flex-col gap-space-sm">
                    <div class="flex items-start justify-between gap-space-sm">
                        <h2 class="font-headline-sm text-headline-sm text-text-primary line-clamp-2 min-w-0">{{ $training->title }}</h2>
                        <span class="font-price-tag text-price-tag text-primary shrink-0">{{ $training->price->format() }}</span>
                    </div>

                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 font-label-sm text-label-sm text-text-secondary">
                        <span class="flex items-center gap-1">
                            <x-icon name="layers" size="15" />
                            {{ trans_choice(':count module|:count modules', (int) $training->contents_count, ['count' => (int) $training->contents_count]) }}
                        </span>

                        <span class="flex items-center gap-1">
                            <x-icon name="groups" size="15" />
                            {{ trans_choice(':count inscrit|:count inscrits', (int) $training->purchases_count, ['count' => (int) $training->purchases_count]) }}
                        </span>

                        @if ($training->included_in_subscription)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-tertiary-fixed text-on-tertiary-fixed">
                                <x-icon name="workspace_premium" size="13" />
                                {{ __('Incluse au Pass') }}
                            </span>
                        @endif
                    </div>

                    @if ($training->rejection_reason)
                        <div class="flex items-start gap-2 rounded-xl bg-[#ffebee] px-3 py-2 font-label-sm text-label-sm text-status-error">
                            <x-icon name="report" size="16" class="shrink-0 mt-0.5" />
                            <span><strong>{{ __('Refusée par la modération') }} :</strong> {{ $training->rejection_reason }}</span>
                        </div>
                    @endif

                    <div class="flex flex-wrap gap-2">
                        @can('update', $training)
                            <a href="{{ route('farmer.trainings.edit', ['training' => $training->slug]) }}" wire:navigate
                               class="h-10 px-4 rounded-full bg-surface-container text-text-primary font-label-lg text-label-lg inline-flex items-center gap-1.5 hover:bg-surface-container-high transition-colors">
                                <x-icon name="edit" size="16" />
                                {{ __('Modifier') }}
                            </a>
                        @endcan

                        @can('submit', $training)
                            <button type="button" wire:click="submit({{ $training->id }})" data-test="submit-training"
                                    class="h-10 px-4 rounded-full bg-primary text-on-primary font-label-lg text-label-lg inline-flex items-center gap-1.5 shadow-card hover:bg-primary-container transition-colors">
                                <x-icon name="send" size="16" />
                                {{ __('Soumettre') }}
                            </button>
                        @endcan

                        @if ($training->status === \App\Enums\PublicationStatus::Published)
                            <a href="{{ route('trainings.show', ['training' => $training->slug]) }}" wire:navigate
                               class="h-10 px-4 rounded-full bg-surface-container text-text-primary font-label-lg text-label-lg inline-flex items-center gap-1.5 hover:bg-surface-container-high transition-colors">
                                <x-icon name="visibility" size="16" />
                                {{ __('Voir la fiche') }}
                            </a>
                        @endif

                        @can('archive', $training)
                            <button type="button" wire:click="archive({{ $training->id }})" data-test="archive-training"
                                    class="h-10 px-4 rounded-full bg-surface-container text-text-secondary font-label-lg text-label-lg inline-flex items-center gap-1.5 hover:bg-surface-container-high transition-colors">
                                <x-icon name="archive" size="16" />
                                {{ __('Archiver') }}
                            </button>
                        @endcan
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-2xl bg-surface-container-lowest flex flex-col items-center gap-space-sm px-space-md py-space-2xl text-center shadow-card">
                <span class="flex w-14 h-14 items-center justify-center rounded-full bg-surface-container-low">
                    <x-icon name="school" size="28" class="text-text-secondary" />
                </span>
                <h2 class="font-headline-sm text-headline-sm text-text-primary">
                    {{ $status !== '' ? __('Aucune formation dans ce statut') : __('Aucune formation pour l\'instant') }}
                </h2>
                <p class="font-body-md text-body-md text-text-secondary max-w-sm">
                    {{ __('Une formation se compose de modules — vidéos ou documents — que vos acheteurs liront dans l\'ordre.') }}
                </p>

                <a href="{{ route('farmer.trainings.create') }}" wire:navigate
                   class="mt-1 h-12 px-6 rounded-full bg-primary text-on-primary font-label-lg text-label-lg inline-flex items-center gap-1.5 shadow-card hover:bg-primary-container transition-colors">
                    <x-icon name="add_circle" size="18" />
                    {{ __('Proposer une formation') }}
                </a>
            </div>
        @endforelse

        @if ($trainings->hasPages())
            <div class="pt-space-xs">{{ $trainings->links() }}</div>
        @endif
    </div>
</div>

{{--
    Carte de formation, reprise de `agritech_catalogue_formations` : vignette
    16/9 barrée d'un dégradé, pastilles de format et d'inclusion en haut à
    gauche, compteur de modules en bas à droite, puis le titre, le formateur
    et un pied de carte teinté portant le prix et l'action.

    La maquette y place aussi une note sur cinq, un nombre d'avis et une
    durée totale. Rien de tout cela n'est stocké : la ligne de méta porte le
    nombre réel de modules et le format.

    `training` doit venir d'une requête `withCount('contents')`, sans quoi le
    compteur de modules retombe sur une requête par carte.
--}}
@props(['training'])

@php
    $profile = $training->farmer->farmerProfile;
    $place = collect([$profile?->city, $profile?->region])->filter()->join(', ');
    $modules = $training->contents_count ?? $training->contents()->count();
@endphp

<article {{ $attributes->class('bg-surface-container-lowest rounded-2xl overflow-hidden shadow-card hover:shadow-raised transition-shadow flex flex-col') }}
         data-test="training-card">
    <a href="{{ route('trainings.show', ['training' => $training->slug]) }}" wire:navigate
       class="relative block w-full aspect-[16/9] bg-surface-container">
        @if ($training->hasCover())
            <img src="{{ $training->coverUrl() }}" alt="{{ $training->title }}" loading="lazy"
                 class="w-full h-full object-cover" />
        @else
            <div class="flex h-full w-full items-center justify-center text-surface-container-highest">
                <x-icon name="school" size="40" />
            </div>
        @endif

        <div class="absolute inset-0 bg-gradient-to-t from-inverse-surface/70 via-transparent to-black/15"></div>

        <div class="absolute top-3 left-3 right-3 flex flex-wrap gap-1.5">
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-inverse-surface/80 backdrop-blur-md text-on-primary font-label-sm text-label-sm">
                <x-icon :name="$training->format === \App\Enums\TrainingFormat::Pdf ? 'description' : 'videocam'" size="14" />
                <span>{{ $training->format->label() }}</span>
            </span>

            @if ($training->included_in_subscription)
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-tertiary-fixed text-on-tertiary-fixed font-label-sm text-label-sm font-semibold shadow-card">
                    <x-icon name="star" size="14" filled />
                    <span>{{ __('Incluse avec abonnement') }}</span>
                </span>
            @endif
        </div>

        @if ($modules > 0)
            <span class="absolute bottom-2.5 right-3 px-2 py-0.5 rounded-md bg-inverse-surface/80 backdrop-blur-md text-on-primary font-label-sm text-[11px] font-medium flex items-center gap-1">
                <x-icon name="layers" size="13" />
                {{ trans_choice(':count module|:count modules', $modules, ['count' => $modules]) }}
            </span>
        @endif
    </a>

    <div class="p-4 flex flex-col gap-3 flex-1">
        <div class="min-w-0">
            <h3 class="font-headline-sm text-headline-sm text-text-primary leading-snug line-clamp-2">
                <a href="{{ route('trainings.show', ['training' => $training->slug]) }}" wire:navigate class="hover:text-primary transition-colors">
                    {{ $training->title }}
                </a>
            </h3>

            <div class="flex items-center gap-2 mt-2 min-w-0">
                <span class="w-8 h-8 rounded-full bg-surface-container-high flex items-center justify-center font-headline-sm text-[12px] text-primary shrink-0">
                    {{ $training->farmer->initials() }}
                </span>
                <div class="flex flex-col min-w-0">
                    <span class="font-label-lg text-label-lg text-text-primary leading-tight truncate">
                        {{ $profile?->farm_name ?? $training->farmer->name }}
                    </span>
                    @if ($place !== '')
                        <span class="font-label-sm text-label-sm text-text-secondary leading-tight truncate">{{ $place }}</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between gap-space-sm bg-surface-container-low -mx-4 -mb-4 mt-auto p-4">
            <div class="flex flex-col min-w-0">
                <span class="font-price-tag text-price-tag text-primary leading-tight">{{ $training->price->format() }}</span>
                @if ($training->included_in_subscription)
                    <span class="font-label-sm text-label-sm text-status-success font-medium">{{ __('Ou incluse dans le Pass') }}</span>
                @endif
            </div>

            <a href="{{ route('trainings.show', ['training' => $training->slug]) }}" wire:navigate
               class="h-10 px-4 rounded-full bg-primary text-on-primary font-label-lg text-label-lg flex items-center gap-1.5 shadow-card shrink-0 hover:bg-primary-container transition-colors">
                <span>{{ __('Voir') }}</span>
                <x-icon name="arrow_forward" size="16" />
            </a>
        </div>
    </div>
</article>

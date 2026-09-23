{{-- Chapeau des écrans d'administration, repris des maquettes
     `agritech_admin_*` : sur-titre en pastille, titre, sous-titre, et la
     place d'une action à droite. --}}
@props(['eyebrow' => null, 'title', 'subtitle' => null, 'icon' => null])

<section {{ $attributes->class('flex flex-wrap items-start justify-between gap-space-sm') }}>
    <div class="min-w-0">
        @if ($eyebrow)
            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-primary-fixed text-primary font-label-sm text-label-sm mb-1">
                @if ($icon)<x-icon :name="$icon" size="14" />@endif
                {{ $eyebrow }}
            </span>
        @endif

        <h1 class="font-headline-md text-headline-md text-text-primary tracking-tight">{{ $title }}</h1>

        @if ($subtitle)
            <p class="font-label-sm text-label-sm text-text-secondary mt-0.5">{{ $subtitle }}</p>
        @endif
    </div>

    @isset($action)
        <div class="shrink-0">{{ $action }}</div>
    @endisset
</section>

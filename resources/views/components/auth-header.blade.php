{{-- Chapeau d'un écran d'authentification : motif de marque, titre, sous-titre.
     Même grammaire que `agritech_connexion`. --}}
@props(['title', 'description' => null, 'icon' => 'eco'])

<div class="flex w-full flex-col items-center text-center gap-space-xs">
    <span class="flex w-16 h-16 items-center justify-center rounded-full bg-primary text-on-primary shadow-raised">
        <x-icon :name="$icon" size="30" filled />
    </span>

    <h1 class="font-headline-lg-mobile text-headline-lg-mobile text-text-primary tracking-tight">{{ $title }}</h1>

    @if ($description)
        <p class="font-body-md text-body-md text-text-secondary">{{ $description }}</p>
    @endif
</div>

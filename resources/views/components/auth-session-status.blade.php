{{-- Message de session des écrans d'authentification (lien envoyé, mot de
     passe réinitialisé…). --}}
@props(['status'])

@if ($status)
    <div {{ $attributes->class('flex items-start gap-2 rounded-xl bg-[#e8f5e9] p-space-sm font-label-lg text-label-lg text-status-success') }}>
        <x-icon name="check_circle" size="18" class="shrink-0 mt-0.5" />
        <span>{{ $status }}</span>
    </div>
@endif

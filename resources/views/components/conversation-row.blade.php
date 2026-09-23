{{--
    Une ligne de la liste de conversations, reprise de `agritech_messages` :
    pastille d'initiales de 56 px, nom et heure, sous-titre, aperçu du dernier
    message sur deux lignes, puis l'étiquette et le compteur de non-lus.

    La maquette y met une photo de profil et une pastille « en ligne ». Aucun
    compte ne porte de photo, et rien ne suit la présence : la pastille indique
    ce que le serveur sait — un fil qui attend une réponse de votre part.
--}}
@props(['conversation', 'counterpart', 'href', 'unread' => 0, 'subtitle' => null, 'reader'])

@php
    $last = $conversation->messages->first();
    $profile = $counterpart->farmerProfile;
    $name = $profile?->farm_name ?? $counterpart->name;
    $place = collect([$profile?->city, $profile?->region])->filter()->join(', ');
@endphp

<a href="{{ $href }}" wire:navigate
   class="relative flex items-start gap-space-sm p-space-md rounded-2xl bg-surface-container-lowest shadow-card hover:shadow-raised transition-shadow"
   data-test="conversation">
    <div class="relative shrink-0">
        <span class="w-14 h-14 rounded-full bg-primary-fixed flex items-center justify-center font-headline-md text-primary">
            {{ $counterpart->initials() }}
        </span>

        @if ($unread > 0)
            <span class="absolute bottom-0 right-0 w-3.5 h-3.5 bg-status-success rounded-full ring-2 ring-surface-container-lowest"
                  aria-label="{{ __('Messages non lus') }}"></span>
        @endif
    </div>

    <div class="flex-1 min-w-0">
        <div class="flex items-center justify-between gap-space-xs mb-0.5">
            <span class="font-headline-sm text-headline-sm text-text-primary truncate">{{ $name }}</span>

            @if ($conversation->last_message_at)
                <time datetime="{{ $conversation->last_message_at->toIso8601String() }}"
                      @class([
                          'font-label-sm text-label-sm shrink-0 font-semibold',
                          'text-status-success' => $unread > 0,
                          'text-text-secondary' => $unread === 0,
                      ])>
                    {{ $conversation->last_message_at->timezone(config('app.timezone'))->diffForHumans(short: true) }}
                </time>
            @endif
        </div>

        <div class="flex items-center gap-1.5 mb-1.5 min-w-0">
            @if ($subtitle)
                <span class="font-label-sm text-label-sm text-text-secondary truncate">{{ $subtitle }}</span>
            @endif

            @if ($subtitle && $place !== '')
                <span class="w-1 h-1 rounded-full bg-text-secondary opacity-40 shrink-0"></span>
            @endif

            @if ($place !== '')
                <span class="font-label-sm text-label-sm text-text-secondary flex items-center gap-0.5 shrink-0">
                    <x-icon name="location_on" size="13" class="text-secondary" />
                    {{ $place }}
                </span>
            @endif
        </div>

        @if ($last)
            <p @class([
                'font-body-md text-body-md line-clamp-2 leading-snug',
                'text-text-primary' => $unread > 0,
                'text-text-secondary' => $unread === 0,
            ])>
                @if ($last->sender_id === $reader->id)
                    <span class="text-text-secondary">{{ __('Vous :') }}</span>
                @endif
                {{ $last->content }}
            </p>
        @else
            <p class="font-body-md text-body-md text-text-secondary italic">{{ __('Aucun message pour l\'instant.') }}</p>
        @endif

        @if ($unread > 0)
            <div class="mt-2.5 flex items-center justify-end">
                <span class="h-5 min-w-[20px] px-1.5 rounded-full bg-primary text-on-primary font-label-sm text-[11px] font-bold flex items-center justify-center">
                    {{ $unread > 9 ? '9+' : $unread }}
                </span>
            </div>
        @endif
    </div>
</a>

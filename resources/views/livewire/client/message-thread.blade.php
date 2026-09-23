{{--
    Reproduction de `agritech_conversation` : en-tête avec retour et
    correspondant, séparateurs de date, bulles reçues à gauche et envoyées à
    droite avec heure et accusé de lecture, puis la barre de saisie épinglée.

    Ce que la maquette montre et que la plateforme n'a pas disparaît : la
    pastille « En ligne » (rien ne suit la présence), le bouton d'appel (aucun
    numéro n'est exposé), le bandeau produit épinglé (une conversation n'est
    pas rattachée à un produit), les pièces jointes et les notes vocales (un
    message ne porte que du texte). L'accusé de lecture, lui, reste : il lit
    `messages.read_at`, que le service écrit vraiment.
--}}
@php($other = $this->counterpart())
@php($profile = $other->farmerProfile)

<div class="flex w-full flex-1 flex-col pb-24 lg:pb-0">
    {{-- En-tête du fil --}}
    <div class="flex items-center gap-space-xs min-w-0 pb-space-sm">
        <a href="{{ route('client.messages') }}" wire:navigate aria-label="{{ __('Retour aux messages') }}"
           class="w-11 h-11 rounded-full flex items-center justify-center text-text-primary hover:bg-surface-container transition-colors shrink-0">
            <x-icon name="arrow_back" size="24" />
        </a>

        <span class="w-10 h-10 rounded-full bg-primary-fixed flex items-center justify-center font-headline-sm text-[13px] text-primary shrink-0">
            {{ $other->initials() }}
        </span>

        <div class="flex flex-col min-w-0 flex-1">
            <span class="font-headline-sm text-headline-sm text-text-primary truncate">
                {{ $profile?->farm_name ?? $other->name }}
            </span>
            <span class="font-label-sm text-label-sm text-text-secondary leading-tight truncate">
                {{ __('Producteur') }}{{ $profile?->city ? ' · '.$profile->city : '' }}
            </span>
        </div>
    </div>

    {{-- Fil --}}
    <div class="flex flex-col gap-space-md flex-1" wire:poll.5s data-test="thread">
        @forelse ($this->days() as $day => $messages)
            <div class="flex items-center justify-center">
                <span class="px-3 py-1 bg-surface-container rounded-full font-label-sm text-label-sm text-text-secondary shadow-card">
                    {{ $day }}
                </span>
            </div>

            @foreach ($messages as $message)
                @php($mine = $message->sender_id === auth()->id())

                @if ($mine)
                    <div class="flex flex-col items-end self-end max-w-[85%]" data-test="message">
                        <div class="bg-primary text-on-primary px-space-md py-space-sm rounded-2xl rounded-br-sm shadow-card">
                            <p class="font-body-md text-body-md leading-relaxed whitespace-pre-line">{{ $message->content }}</p>
                        </div>

                        <div class="flex items-center gap-1 px-space-xs pt-1">
                            <span class="font-label-sm text-[11px] text-text-secondary">
                                {{ $message->created_at?->timezone(config('app.timezone'))->format('H:i') }}
                            </span>
                            {{-- Accusé de lecture réel : `messages.read_at` est
                                 écrit quand le destinataire ouvre le fil. --}}
                            <x-icon :name="$message->read_at ? 'done_all' : 'done'" size="15"
                                    :class="$message->read_at ? 'text-primary' : 'text-text-secondary'" />
                        </div>
                    </div>
                @else
                    <div class="flex items-end gap-space-xs max-w-[85%] self-start" data-test="message">
                        <span class="w-7 h-7 rounded-full bg-secondary-fixed text-on-secondary-fixed-variant flex items-center justify-center font-headline-sm text-[11px] shrink-0 mb-1 shadow-card">
                            {{ $message->sender->initials() }}
                        </span>

                        <div class="flex flex-col items-start min-w-0">
                            <div class="bg-surface-container-low text-text-primary px-space-md py-space-sm rounded-2xl rounded-bl-sm shadow-card">
                                <p class="font-body-md text-body-md leading-relaxed whitespace-pre-line">{{ $message->content }}</p>
                            </div>
                            <span class="font-label-sm text-[11px] text-text-secondary px-space-xs pt-1">
                                {{ $message->created_at?->timezone(config('app.timezone'))->format('H:i') }}
                            </span>
                        </div>
                    </div>
                @endif
            @endforeach
        @empty
            <div class="flex flex-col items-center gap-space-sm px-space-md py-space-2xl text-center">
                <span class="flex w-14 h-14 items-center justify-center rounded-full bg-surface-container-low">
                    <x-icon name="forum" size="28" class="text-text-secondary" />
                </span>
                <h2 class="font-headline-sm text-headline-sm text-text-primary">{{ __('Le fil est vide') }}</h2>
                <p class="font-body-md text-body-md text-text-secondary max-w-sm">
                    {{ __('Écrivez le premier message : votre correspondant recevra une notification.') }}
                </p>
            </div>
        @endforelse
    </div>

    {{-- Barre de saisie --}}
    <form wire:submit="send"
          class="fixed bottom-16 lg:bottom-0 inset-x-0 lg:left-64 z-40 bg-surface/95 backdrop-blur-xl shadow-[0_-4px_16px_rgba(31,36,33,0.06)] px-margin py-2.5"
          style="padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 0.625rem);">
        <div class="max-w-screen-md mx-auto flex items-center gap-2">
            <div class="flex-1 min-w-0 bg-surface-container rounded-full px-3.5 py-1.5 flex items-center gap-2">
                <input type="text" wire:model="content" maxlength="5000" data-test="content"
                       placeholder="{{ __('Écrire à :name…', ['name' => $profile?->farm_name ?? $other->first_name]) }}"
                       class="w-full bg-transparent border-0 outline-none text-text-primary font-body-md text-[15px] placeholder:text-text-secondary/70 min-w-0 h-9" />
            </div>

            <button type="submit" data-test="send" wire:loading.attr="disabled"
                    aria-label="{{ __('Envoyer') }}"
                    class="w-11 h-11 rounded-full bg-primary text-on-primary flex items-center justify-center shadow-raised hover:bg-primary-container transition-colors shrink-0 disabled:opacity-60">
                <x-icon name="send" size="20" />
            </button>
        </div>

        @error('content')
            <p class="max-w-screen-md mx-auto flex items-center gap-1 font-label-sm text-label-sm text-status-error pt-1.5">
                <x-icon name="error" size="14" />
                {{ $message }}
            </p>
        @enderror
    </form>
</div>

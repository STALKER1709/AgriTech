{{--
    Reproduction de `agritech_lecteur_de_formation` : bandeau de contexte,
    zone de lecture, informations du module, carte du formateur, onglets et
    liste des modules.

    Ce que la maquette montre et que rien ne stocke disparaît : la progression
    (« 67 % · 8/12 validés »), le téléchargement hors-ligne, l'onglet « Notes &
    Discussion ». Les commandes de lecture maison (vitesse, sous-titres, plein
    écran, retour de 10 secondes) laissent la place aux commandes natives du
    navigateur, qui font le même travail et le font vraiment.
--}}
<div class="flex w-full flex-col gap-space-md">
    {{-- Bandeau de contexte --}}
    <div class="-mx-margin lg:mx-0 px-margin lg:px-space-md py-space-xs bg-surface-container lg:rounded-xl flex items-center justify-between gap-space-sm shadow-card">
        <div class="flex items-center gap-space-xs min-w-0">
            <x-icon name="eco" size="20" filled class="text-primary shrink-0" />
            <span class="font-label-lg text-label-lg text-on-surface truncate">{{ $training->title }}</span>
        </div>

        <a href="{{ route('trainings.show', ['training' => $training->slug]) }}" wire:navigate
           class="shrink-0 flex items-center gap-1 bg-surface-container-highest hover:bg-surface-dim px-3 py-1.5 rounded-full transition-colors">
            <x-icon name="info" size="18" class="text-text-secondary" />
            <span class="font-label-sm text-label-sm text-text-secondary font-semibold">{{ __('La fiche') }}</span>
        </a>
    </div>

    {{-- Zone de lecture --}}
    <div class="-mx-margin lg:mx-0 bg-inverse-surface lg:rounded-xl overflow-hidden">
        @if ($content->type === \App\Enums\TrainingContentType::Video)
            <video controls preload="metadata" class="w-full aspect-video bg-inverse-surface"
                   src="{{ route('trainings.content', ['content' => $content->id]) }}"
                   data-test="player">
                {{ __('Votre navigateur ne sait pas lire cette vidéo.') }}
            </video>
        @else
            <iframe title="{{ $content->title }}" data-test="player"
                    src="{{ route('trainings.content', ['content' => $content->id]) }}"
                    class="w-full h-[60vh] min-h-[360px] bg-surface-container-lowest"></iframe>
        @endif
    </div>

    {{-- Module en cours --}}
    <section class="flex flex-col gap-space-sm">
        <div class="flex flex-col gap-1">
            <div class="flex flex-wrap items-center gap-2">
                <span class="bg-secondary-fixed text-on-secondary-fixed-variant font-label-sm text-label-sm px-2 py-0.5 rounded-full font-bold uppercase tracking-wider">
                    {{ __('Module :current sur :total', ['current' => $this->currentIndex(), 'total' => $this->modules()->count()]) }}
                </span>
                <span class="text-text-secondary font-label-sm text-label-sm flex items-center gap-1">
                    <x-icon :name="$content->type === \App\Enums\TrainingContentType::Video ? 'play_circle' : 'description'" size="15" />
                    {{ $content->type->label() }}
                </span>
            </div>

            <h1 class="font-headline-sm text-headline-sm text-on-surface leading-snug">{{ $content->title }}</h1>
        </div>

        {{-- Formateur --}}
        @php($profile = $training->farmer->farmerProfile)
        <div class="bg-surface-container-low rounded-xl p-3 flex items-center justify-between gap-space-sm">
            <div class="flex items-center gap-space-xs min-w-0">
                <span class="w-11 h-11 rounded-full bg-surface-container-high flex items-center justify-center font-headline-sm text-[13px] text-primary shrink-0">
                    {{ $training->farmer->initials() }}
                </span>
                <div class="flex flex-col min-w-0">
                    <div class="flex items-center gap-1 min-w-0">
                        <span class="font-body-md-bold text-body-md-bold text-on-surface truncate">
                            {{ $profile?->farm_name ?? $training->farmer->name }}
                        </span>
                        <x-icon name="verified" size="16" filled class="text-primary shrink-0" />
                    </div>
                    <span class="font-label-sm text-label-sm text-text-secondary truncate">
                        {{ collect([$profile?->city, $profile?->region])->filter()->join(', ') }}
                    </span>
                </div>
            </div>

            <a href="{{ $this->askUrl() }}" wire:navigate
               class="shrink-0 bg-primary-fixed text-primary hover:bg-primary-fixed-dim font-label-sm text-label-sm font-semibold px-3 py-2 rounded-full transition-colors flex items-center gap-1">
                <x-icon name="chat" size="16" />
                <span class="hidden sm:inline">{{ __('Poser une question') }}</span>
                <span class="sm:hidden">{{ __('Question') }}</span>
            </a>
        </div>
    </section>

    {{-- Navigation d'un module à l'autre --}}
    <div class="grid grid-cols-2 gap-space-xs">
        @php($previous = $this->previous())
        @php($next = $this->next())

        <button type="button" @disabled(! $previous)
                @if ($previous) wire:click="open({{ $previous->id }})" @endif
                class="h-12 px-3 rounded-full bg-surface-container-low text-text-primary font-label-lg text-label-lg flex items-center justify-center gap-1.5 hover:bg-surface-container-high transition-colors disabled:opacity-40 disabled:hover:bg-surface-container-low">
            <x-icon name="arrow_back" size="18" />
            <span class="truncate">{{ __('Précédent') }}</span>
        </button>

        <button type="button" @disabled(! $next)
                @if ($next) wire:click="open({{ $next->id }})" @endif
                class="h-12 px-3 rounded-full bg-primary text-on-primary font-label-lg text-label-lg flex items-center justify-center gap-1.5 hover:bg-primary-container transition-colors shadow-card disabled:opacity-40"
                data-test="next-module">
            <span class="truncate">{{ __('Module suivant') }}</span>
            <x-icon name="arrow_forward" size="18" />
        </button>
    </div>

    {{-- Onglets et liste des modules --}}
    <section class="flex flex-col gap-space-sm" x-data="{ tab: 'modules' }">
        @if ($this->hasMixedContent())
            <div class="flex items-center gap-2 border-b border-surface-container-high overflow-x-auto no-scrollbar">
                <button type="button" x-on:click="tab = 'modules'"
                        :class="tab === 'modules' ? 'text-primary font-bold border-primary' : 'text-text-secondary border-transparent hover:text-on-surface'"
                        class="pb-2.5 px-3 font-label-lg text-label-lg border-b-2 shrink-0 transition-colors">
                    {{ __('Modules du cours') }}
                </button>
                <button type="button" x-on:click="tab = 'documents'"
                        :class="tab === 'documents' ? 'text-primary font-bold border-primary' : 'text-text-secondary border-transparent hover:text-on-surface'"
                        class="pb-2.5 px-3 font-label-lg text-label-lg border-b-2 shrink-0 transition-colors flex items-center gap-1">
                    <span>{{ __('Documents') }}</span>
                    <span class="bg-surface-container-highest text-text-primary px-1.5 text-[11px] rounded-full">{{ $this->documents()->count() }}</span>
                </button>
            </div>
        @else
            <h2 class="font-headline-sm text-headline-sm text-text-primary">{{ __('Modules du cours') }}</h2>
        @endif

        <div class="flex flex-col gap-2.5" x-show="tab === 'modules'">
            @foreach ($this->modules() as $index => $module)
                <x-training-module :module="$module" :number="$index + 1" :current="$module->id === $content->id" />
            @endforeach
        </div>

        @if ($this->hasMixedContent())
            <div class="flex flex-col gap-2.5" x-show="tab === 'documents'" x-cloak>
                @foreach ($this->documents() as $document)
                    <x-training-module :module="$document"
                                       :number="$this->modules()->search(fn ($item) => $item->id === $document->id) + 1"
                                       :current="$document->id === $content->id" />
                @endforeach
            </div>
        @endif
    </section>
</div>

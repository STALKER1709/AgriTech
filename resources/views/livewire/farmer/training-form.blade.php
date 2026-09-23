{{--
    Formulaire de formation. La maquette `agritech_formulaire_produit` est la
    seule des deux à être dessinée ; cet écran en reprend la grammaire —
    retour, bandeau de modération, sections titrées, champs à icône — et
    ajoute ce qui lui est propre : la liste des modules et leur dépôt.
--}}
<div class="flex w-full max-w-2xl flex-1 flex-col gap-space-md">
    <div class="flex items-center gap-space-sm min-w-0">
        <a href="{{ route('farmer.trainings') }}" wire:navigate aria-label="{{ __('Retour aux formations') }}"
           class="w-11 h-11 rounded-full flex items-center justify-center text-text-primary hover:bg-surface-container transition-colors shrink-0">
            <x-icon name="arrow_back" size="24" />
        </a>

        <div class="min-w-0">
            <h1 class="font-headline-md text-headline-md text-text-primary tracking-tight truncate">
                {{ $training?->exists ? __('Modifier la formation') : __('Nouvelle formation') }}
            </h1>
            <p class="font-label-sm text-label-sm text-text-secondary">
                {{ __('Enregistrée en brouillon ; la soumission se fait depuis la liste.') }}
            </p>
        </div>
    </div>

    @if ($training?->exists && $training->rejection_reason)
        <div class="rounded-xl bg-[#ffebee] p-space-md flex items-start gap-space-sm" data-test="rejection-reason">
            <span class="w-9 h-9 rounded-full bg-status-error text-on-error flex items-center justify-center shrink-0">
                <x-icon name="priority_high" size="18" />
            </span>
            <div class="min-w-0">
                <span class="font-body-md-bold text-body-md-bold text-status-error block">
                    {{ __('Formation refusée à la modération') }}
                </span>
                <p class="font-body-md text-body-md text-text-primary leading-relaxed mt-0.5">{{ $training->rejection_reason }}</p>
            </div>
        </div>
    @endif

    <form wire:submit="save" class="flex flex-col gap-space-md">
        <section class="rounded-2xl bg-surface-container-lowest p-space-md shadow-card flex flex-col gap-space-md">
            <div class="flex items-center gap-space-xs">
                <x-icon name="description" size="20" class="text-primary" />
                <h2 class="font-headline-sm text-headline-sm text-text-primary">{{ __('Informations générales') }}</h2>
            </div>

            <x-form-field wire="title" :label="__('Titre de la formation')" icon="label" required
                          :placeholder="__('Ex : Composter ses déchets agricoles')" />

            <x-form-field wire="description" type="textarea" :label="__('Description')" icon="notes" required
                          :rows="5"
                          :placeholder="__('Objectifs, public visé, prérequis, déroulé des modules…')"
                          :hint="__('C\'est ce que lit l\'acheteur avant de payer.')" />
        </section>

        <section class="rounded-2xl bg-surface-container-lowest p-space-md shadow-card flex flex-col gap-space-md">
            <div class="flex items-center gap-space-xs">
                <x-icon name="sell" size="20" class="text-primary" />
                <h2 class="font-headline-sm text-headline-sm text-text-primary">{{ __('Prix et format') }}</h2>
            </div>

            <div class="grid gap-space-md sm:grid-cols-2">
                <x-form-field wire="price" :label="__('Prix')" suffix="FCFA" required
                              inputmode="numeric" placeholder="5 000"
                              :hint="__('Un entier, sans centimes.')" />

                <x-form-field wire="format" type="select" :label="__('Format')" icon="movie" required
                              :options="collect($this->formats())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->all()"
                              :hint="__('Ce que l\'acheteur recevra vraiment.')" />
            </div>

            <label class="flex items-start gap-2.5 cursor-pointer rounded-xl bg-surface-container-low p-space-sm">
                <input type="checkbox" wire:model="included_in_subscription"
                       class="w-5 h-5 mt-0.5 rounded-md border-outline-variant text-primary focus:ring-primary shrink-0" />
                <span class="min-w-0">
                    <span class="font-label-lg text-label-lg text-text-primary block">{{ __('Inclure dans le Pass Formations') }}</span>
                    <span class="font-label-sm text-label-sm text-text-secondary">
                        {{ __('Les abonnés actifs y accèdent sans achat supplémentaire.') }}
                    </span>
                </span>
            </label>
        </section>

        <div class="flex flex-wrap gap-space-sm">
            <button type="submit" data-test="save-training" wire:loading.attr="disabled"
                    class="h-14 flex-1 min-w-[200px] rounded-full bg-primary text-on-primary font-label-lg text-label-lg flex items-center justify-center gap-2 shadow-raised hover:bg-primary-container transition-colors disabled:opacity-60">
                <x-icon name="save" size="20" />
                <span wire:loading.remove wire:target="save">{{ __('Enregistrer') }}</span>
                <span wire:loading wire:target="save">{{ __('Enregistrement…') }}</span>
            </button>

            <a href="{{ route('farmer.trainings') }}" wire:navigate
               class="h-14 px-6 rounded-full bg-surface-container text-text-primary font-label-lg text-label-lg inline-flex items-center justify-center gap-1.5 hover:bg-surface-container-high transition-colors">
                {{ __('Annuler') }}
            </a>
        </div>
    </form>

    {{-- Modules. Ils n'existent qu'une fois la formation enregistrée : un
         fichier a besoin d'une formation à qui appartenir. --}}
    @if ($training?->exists)
        <section class="rounded-2xl bg-surface-container-lowest p-space-md shadow-card flex flex-col gap-space-md">
            <div class="flex items-center gap-space-xs">
                <x-icon name="layers" size="20" class="text-primary" />
                <h2 class="font-headline-sm text-headline-sm text-text-primary">{{ __('Modules de la formation') }}</h2>
            </div>

            <p class="font-label-sm text-label-sm text-text-secondary flex items-start gap-1.5 -mt-2">
                <x-icon name="shield" size="15" class="text-primary shrink-0 mt-0.5" />
                {{ __('Les fichiers vivent sur un disque privé et ne sont servis qu\'aux ayants droit.') }}
            </p>

            <div class="flex flex-col gap-2">
                @forelse ($this->contents() as $index => $content)
                    <div class="flex items-center justify-between gap-space-sm rounded-xl bg-surface-container-low px-space-sm py-2.5">
                        <div class="flex min-w-0 items-center gap-2.5">
                            <span class="w-8 h-8 rounded-full bg-surface-container-high text-text-secondary flex items-center justify-center font-label-sm text-label-sm font-bold shrink-0">
                                {{ $index + 1 }}
                            </span>

                            <div class="min-w-0">
                                <span class="font-label-lg text-label-lg text-text-primary truncate block">{{ $content->title }}</span>
                                <span class="font-label-sm text-label-sm text-text-secondary flex items-center gap-1">
                                    <x-icon :name="$content->type === \App\Enums\TrainingContentType::Video ? 'play_circle' : 'description'" size="14" />
                                    {{ $content->type->label() }}
                                </span>
                            </div>
                        </div>

                        <button type="button" wire:click="removeContent({{ $content->id }})"
                                wire:confirm="{{ __('Supprimer ce module ?') }}"
                                aria-label="{{ __('Supprimer ce module') }}"
                                class="w-9 h-9 rounded-full bg-surface-container text-status-error flex items-center justify-center shrink-0 hover:bg-surface-container-high transition-colors">
                            <x-icon name="delete" size="18" />
                        </button>
                    </div>
                @empty
                    <p class="font-body-md text-body-md text-text-secondary">
                        {{ __('Aucun module déposé pour l\'instant.') }}
                    </p>
                @endforelse
            </div>

            <form wire:submit="addContent" class="flex flex-col gap-space-sm rounded-xl border-2 border-dashed border-outline-variant bg-surface-container-low p-space-md">
                <div class="grid gap-space-sm sm:grid-cols-2">
                    <x-form-field wire="content_title" :label="__('Titre du module')" icon="label" required
                                  :placeholder="__('Ex : Monter un tas de compost')" />

                    <x-form-field wire="content_type" type="select" :label="__('Type')" icon="category" required
                                  :options="collect($this->contentTypes())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->all()" />
                </div>

                <label class="flex flex-col items-center justify-center gap-1.5 rounded-xl bg-surface-container-lowest p-space-md cursor-pointer hover:bg-surface-container transition-colors">
                    <x-icon name="upload_file" size="28" class="text-primary" />
                    <span class="font-label-lg text-label-lg text-text-primary">{{ __('Choisir le fichier') }}</span>
                    <span class="font-label-sm text-label-sm text-text-secondary text-center">
                        {{ __('MP4, WebM ou PDF, :max Mo au plus. Le nom du fichier n\'est pas conservé.', [
                            'max' => (int) (config('trainings.contents.max_kilobytes') / 1024),
                        ]) }}
                    </span>
                    <input type="file" wire:model="uploads" accept="video/mp4,video/webm,application/pdf" class="sr-only" required />
                </label>

                <p wire:loading wire:target="uploads" class="font-label-sm text-label-sm text-primary flex items-center gap-1.5">
                    <x-icon name="sync" size="15" />
                    {{ __('Téléversement en cours…') }}
                </p>

                @error('uploads.0')
                    <p class="flex items-center gap-1 font-label-sm text-label-sm text-status-error">
                        <x-icon name="error" size="14" />
                        {{ $message }}
                    </p>
                @enderror

                <button type="submit" wire:loading.attr="disabled" data-test="add-content"
                        class="h-12 rounded-full bg-secondary text-on-secondary font-label-lg text-label-lg flex items-center justify-center gap-2 shadow-card hover:opacity-95 transition-opacity disabled:opacity-60">
                    <x-icon name="add" size="18" />
                    {{ __('Ajouter le module') }}
                </button>
            </form>
        </section>
    @endif
</div>

<div class="flex w-full max-w-2xl flex-1 flex-col gap-6">
    <div>
        <flux:heading size="xl" level="1">
            {{ $training?->exists ? __('Modifier la formation') : __('Nouvelle formation') }}
        </flux:heading>
        <flux:text class="mt-2">
            {{ __('La formation est enregistrée en brouillon. Vous la soumettrez à publication depuis la liste.') }}
        </flux:text>
    </div>

    <form wire:submit="save" class="flex flex-col gap-4">
        <flux:input wire:model="title" :label="__('Titre de la formation')" type="text" required autofocus />

        <flux:textarea
            wire:model="description"
            :label="__('Description')"
            :description="__('Objectifs, public visé, prérequis, déroulé des modules…')"
            rows="5"
            required
        />

        <div class="grid gap-4 sm:grid-cols-2">
            <flux:input
                wire:model="price"
                :label="__('Prix (FCFA)')"
                :description="__('Nombre entier, sans centimes.')"
                type="text"
                inputmode="numeric"
                required
            />

            <flux:select wire:model="format" :label="__('Format')" required>
                @foreach ($this->formats() as $formatOption)
                    <flux:select.option value="{{ $formatOption->value }}">{{ $formatOption->label() }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <flux:checkbox wire:model="included_in_subscription" :label="__('Inclure dans l\'abonnement')" :description="__('Les abonnés actifs y accèdent sans achat supplémentaire.')" />

        <div class="flex flex-wrap gap-2">
            <flux:button variant="primary" type="submit" data-test="save-training">
                <span wire:loading.remove wire:target="save">{{ __('Enregistrer') }}</span>
                <span wire:loading wire:target="save">{{ __('Enregistrement…') }}</span>
            </flux:button>

            <flux:button variant="ghost" :href="route('farmer.trainings')" wire:navigate>{{ __('Annuler') }}</flux:button>
        </div>
    </form>

    @if ($training?->exists)
        <flux:separator />

        <div class="flex flex-col gap-3">
            <div>
                <flux:heading size="sm">{{ __('Contenus de la formation') }}</flux:heading>
                <flux:text class="mt-1 text-sm">
                    {{ __('Vidéos et documents servis uniquement aux clients autorisés.') }}
                </flux:text>
            </div>

            <div class="flex flex-col gap-2">
                @forelse ($this->contents() as $content)
                    <div class="flex items-center justify-between gap-3 rounded-lg border border-neutral-200 px-3 py-2 dark:border-neutral-700">
                        <div class="flex min-w-0 items-center gap-2">
                            @if ($content->type->value === 'pdf')
                                <flux:icon.document-text class="size-5 shrink-0 text-zinc-400" />
                            @else
                                <flux:icon.video-camera class="size-5 shrink-0 text-zinc-400" />
                            @endif

                            <div class="min-w-0">
                                <flux:text class="truncate">{{ $content->title }}</flux:text>
                                <flux:text class="text-xs text-zinc-500">{{ $content->type->label() }}</flux:text>
                            </div>
                        </div>

                        <flux:button
                            size="xs"
                            variant="danger"
                            wire:click="removeContent({{ $content->id }})"
                            wire:confirm="{{ __('Supprimer ce contenu ?') }}"
                        >
                            ×
                        </flux:button>
                    </div>
                @empty
                    <flux:text class="text-sm text-zinc-500">{{ __('Aucun contenu déposé pour l\'instant.') }}</flux:text>
                @endforelse
            </div>

            <form wire:submit="addContent" class="flex flex-col gap-3 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                <flux:input wire:model="content_title" :label="__('Titre du module')" type="text" required />

                <flux:select wire:model="content_type" :label="__('Type de contenu')" required>
                    @foreach ($this->contentTypes() as $typeOption)
                        <flux:select.option value="{{ $typeOption->value }}">{{ $typeOption->label() }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input
                    type="file"
                    wire:model="uploads"
                    accept="video/mp4,video/webm,application/pdf"
                    :label="__('Fichier')"
                    :description="__('MP4, WebM ou PDF, :max Mo maximum. Le nom du fichier n\'est pas conservé.', ['max' => (int) (config('trainings.contents.max_kilobytes') / 1024)])"
                    required
                />

                <div>
                    <flux:button variant="secondary" type="submit" icon="arrow-up-tray" wire:loading.attr="disabled">
                        {{ __('Ajouter le contenu') }}
                    </flux:button>
                </div>
            </form>
        </div>
    @endIf
</div>

<div class="flex w-full max-w-3xl flex-1 flex-col gap-5">
    {{-- En-tête façon « Paramètres généraux de la plateforme » Stitch --}}
    <div class="flex items-center gap-3">
        <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-stitch-primary/10 text-stitch-primary">
            <flux:icon.adjustments-horizontal class="size-5" />
        </span>
        <div>
            <h1 class="text-xl font-bold">{{ __('Paramètres de la plateforme') }}</h1>
            <p class="text-sm text-stitch-muted">
                {{ __('Ces valeurs s\'appliquent immédiatement. Les montants déjà figés sur une commande ne changent pas.') }}
            </p>
        </div>
    </div>

    <form wire:submit="save" class="flex flex-col gap-5">
        @foreach ($settings as $setting)
            <div class="stitch-card p-4">
                @if ($setting->type === \App\Enums\SettingType::Boolean)
                    <flux:switch
                        wire:model="values.{{ $setting->key }}"
                        :label="$setting->label"
                        :description="$setting->description"
                    />
                @else
                    <flux:input
                        wire:model="values.{{ $setting->key }}"
                        :label="$setting->label"
                        :description="$setting->description"
                        type="text"
                        inputmode="{{ $setting->type === \App\Enums\SettingType::Integer ? 'numeric' : 'text' }}"
                    />
                @endif
            </div>
        @endforeach

        <div>
            <flux:button variant="primary" type="submit" data-test="save-settings">
                <span wire:loading.remove wire:target="save">{{ __('Enregistrer') }}</span>
                <span wire:loading wire:target="save">{{ __('Enregistrement…') }}</span>
            </flux:button>
        </div>
    </form>
</div>

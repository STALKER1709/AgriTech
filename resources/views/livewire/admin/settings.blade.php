<div class="flex w-full max-w-3xl flex-1 flex-col gap-6">
    <div>
        <flux:heading size="xl" level="1">{{ __('Paramètres de la plateforme') }}</flux:heading>
        <flux:text class="mt-2">
            {{ __('Ces valeurs s\'appliquent immédiatement. Les montants déjà figés sur une commande ne changent pas.') }}
        </flux:text>
    </div>

    <form wire:submit="save" class="flex flex-col gap-5">
        @foreach ($settings as $setting)
            <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
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

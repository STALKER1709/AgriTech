<x-layouts::app :title="__('Espace client')">
    <div class="flex w-full flex-1 flex-col gap-6">
        <div>
            <flux:heading size="xl" level="1">{{ __('Espace client') }}</flux:heading>
            <flux:text class="mt-2">{{ __('Retrouvez vos commandes, vos formations et vos échanges avec les agriculteurs.') }}</flux:text>
        </div>

        <flux:callout icon="information-circle">
            <flux:callout.heading>{{ __('Espace en construction') }}</flux:callout.heading>
            <flux:callout.text>
                {{ __('Les écrans de cet espace arrivent dans les prochaines étapes du projet.') }}
            </flux:callout.text>
        </flux:callout>
    </div>
</x-layouts::app>

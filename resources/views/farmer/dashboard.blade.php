<x-layouts::app :title="__('Espace agriculteur')">
    <div class="flex w-full flex-1 flex-col gap-6">
        <div>
            <flux:heading size="xl" level="1">{{ __('Espace agriculteur') }}</flux:heading>
            <flux:text class="mt-2">{{ __('Gérez vos produits, vos formations et les commandes de vos clients.') }}</flux:text>
        </div>

        <flux:callout icon="information-circle">
            <flux:callout.heading>{{ __('Espace en construction') }}</flux:callout.heading>
            <flux:callout.text>
                {{ __('Les écrans de cet espace arrivent dans les prochaines étapes du projet.') }}
            </flux:callout.text>
        </flux:callout>
    </div>
</x-layouts::app>

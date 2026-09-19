<x-layouts::app :title="__('Administration')">
    <div class="flex w-full flex-1 flex-col gap-6">
        <div>
            <flux:heading size="xl" level="1">{{ __('Administration') }}</flux:heading>
            <flux:text class="mt-2">{{ __('Validez les comptes agriculteurs, modérez les publications et suivez l\'activité.') }}</flux:text>
        </div>

        <flux:callout icon="information-circle">
            <flux:callout.heading>{{ __('Espace en construction') }}</flux:callout.heading>
            <flux:callout.text>
                {{ __('Les écrans de cet espace arrivent dans les prochaines étapes du projet.') }}
            </flux:callout.text>
        </flux:callout>
    </div>
</x-layouts::app>

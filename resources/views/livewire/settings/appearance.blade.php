<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading level="2" class="sr-only">{{ __('Paramètres d\'apparence') }}</flux:heading>

    <x-settings.layout :heading="__('Apparence')" :subheading=" __('Choisissez l\'apparence de l\'interface')">
        <flux:radio.group x-data variant="segmented" x-model="$flux.appearance">
            <flux:radio value="light" icon="sun">{{ __('Clair') }}</flux:radio>
            <flux:radio value="dark" icon="moon">{{ __('Sombre') }}</flux:radio>
            <flux:radio value="system" icon="computer-desktop">{{ __('Système') }}</flux:radio>
        </flux:radio.group>
    </x-settings.layout>
</section>

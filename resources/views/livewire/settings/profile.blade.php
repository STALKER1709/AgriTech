<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading level="2" class="sr-only">{{ __('Paramètres du profil') }}</flux:heading>

    <x-settings.layout :heading="__('Profil')" :subheading="__('Mettez à jour vos informations de contact')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <flux:input wire:model="first_name" :label="__('Prénom')" type="text" required autofocus autocomplete="given-name" />

            <flux:input wire:model="last_name" :label="__('Nom')" type="text" required autocomplete="family-name" />

            <div>
                <flux:input wire:model="email" :label="__('Adresse e-mail')" type="email" required autocomplete="email" />

                @if ($this->hasUnverifiedEmail)
                    <div>
                        <flux:text class="mt-4">
                            {{ __('Votre adresse e-mail n\'est pas vérifiée.') }}

                            <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                {{ __('Cliquez ici pour recevoir un nouvel e-mail de vérification.') }}
                            </flux:link>
                        </flux:text>

                    </div>
                @endif
            </div>

            <flux:input wire:model="phone" :label="__('Numéro de téléphone')" type="tel" required autocomplete="tel" />

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit">{{ __('Enregistrer') }}</flux:button>
            </div>
        </form>

        @if ($this->showDeleteUser)
            <livewire:settings.delete-user-form />
        @endif
    </x-settings.layout>
</section>

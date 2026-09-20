<div class="flex flex-col gap-6">
    <x-auth-header
        :title="__('Créer un compte agriculteur')"
        :description="__('Renseignez votre identité et votre exploitation pour vendre sur AgriTech.')"
    />

    <form wire:submit="register" class="flex flex-col gap-6">
        <flux:fieldset>
            <flux:legend>{{ __('Vos informations') }}</flux:legend>

            <div class="flex flex-col gap-4">
                <flux:input
                    wire:model="first_name"
                    :label="__('Prénom')"
                    type="text"
                    required
                    autofocus
                    autocomplete="given-name"
                />

                <flux:input
                    wire:model="last_name"
                    :label="__('Nom')"
                    type="text"
                    required
                    autocomplete="family-name"
                />

                <flux:input
                    wire:model="email"
                    :label="__('Adresse e-mail')"
                    type="email"
                    required
                    autocomplete="email"
                    placeholder="vous@exemple.cm"
                />

                <flux:input
                    wire:model="phone"
                    :label="__('Numéro de téléphone')"
                    :description="__('Format camerounais, par exemple 650 00 00 01.')"
                    type="tel"
                    required
                    autocomplete="tel"
                    placeholder="650 00 00 01"
                />
            </div>
        </flux:fieldset>

        <flux:fieldset>
            <flux:legend>{{ __('Votre exploitation') }}</flux:legend>

            <div class="flex flex-col gap-4">
                <flux:input
                    wire:model="farm_name"
                    :label="__('Nom de l\'exploitation')"
                    type="text"
                    required
                />

                <flux:select wire:model="region" :label="__('Région')" :placeholder="__('Choisissez une région')" required>
                    @foreach ($this->regions() as $region)
                        <flux:select.option value="{{ $region }}">{{ $region }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="city" :label="__('Ville')" type="text" required />

                <flux:textarea
                    wire:model="description"
                    :label="__('Description (facultatif)')"
                    :description="__('Décrivez vos cultures, votre savoir-faire, votre zone de livraison.')"
                    rows="3"
                />
            </div>
        </flux:fieldset>

        <flux:fieldset>
            <flux:legend>{{ __('Votre mot de passe') }}</flux:legend>

            <div class="flex flex-col gap-4">
                <flux:input
                    wire:model="password"
                    :label="__('Mot de passe')"
                    type="password"
                    required
                    autocomplete="new-password"
                    viewable
                />

                <flux:input
                    wire:model="password_confirmation"
                    :label="__('Confirmation du mot de passe')"
                    type="password"
                    required
                    autocomplete="new-password"
                    viewable
                />
            </div>
        </flux:fieldset>

        <flux:callout icon="information-circle">
            <flux:callout.text>
                {{ __('Après l\'inscription, vous devrez régler les frais d\'inscription. Votre compte sera ensuite examiné par un administrateur avant de pouvoir publier.') }}
            </flux:callout.text>
        </flux:callout>

        <flux:button variant="primary" type="submit" class="w-full" data-test="register-farmer-button">
            <span wire:loading.remove wire:target="register">{{ __('Créer mon compte agriculteur') }}</span>
            <span wire:loading wire:target="register">{{ __('Création en cours…') }}</span>
        </flux:button>
    </form>

    <div class="space-x-1 text-sm text-center rtl:space-x-reverse text-stitch-muted ">
        <span>{{ __('Vous avez déjà un compte ?') }}</span>
        <flux:link :href="route('login')" wire:navigate>{{ __('Se connecter') }}</flux:link>
    </div>
</div>

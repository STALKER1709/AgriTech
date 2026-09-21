<div class="flex w-full max-w-2xl flex-1 flex-col gap-5">
    {{-- En-tête façon écran « Compte en attente de validation » Stitch --}}
    <div class="flex items-center gap-3">
        <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-stitch-gold-soft text-[#8d6b00]">
            <flux:icon.clock class="size-5" />
        </span>
        <div>
            <h1 class="text-xl font-bold">{{ __('Statut de mon compte') }}</h1>
            <p class="text-sm text-stitch-muted">
                {{ __('Compte de :name — :status', ['name' => $this->user()->name, 'status' => $this->user()->status->label()]) }}
            </p>
        </div>
    </div>

    @if ($this->awaitsPayment())
        <flux:callout icon="banknotes" variant="warning">
            <flux:callout.heading>{{ __('Frais d\'inscription à régler') }}</flux:callout.heading>
            <flux:callout.text>
                {{ __('Votre compte a bien été créé. Pour qu\'il soit examiné par un administrateur, réglez les frais d\'inscription de :amount.', ['amount' => $this->registrationFee()->format()]) }}
            </flux:callout.text>
        </flux:callout>

        <form wire:submit="payRegistrationFee" class="stitch-card flex flex-col gap-4 p-4">
            <flux:heading size="lg">{{ __('Régler les frais d\'inscription') }}</flux:heading>

            <flux:radio.group wire:model="method" :label="__('Opérateur')" variant="segmented">
                @foreach ($this->methods() as $method)
                    <flux:radio value="{{ $method->value }}" :label="$method->label()" />
                @endforeach
            </flux:radio.group>

            <flux:input
                wire:model="phone"
                :label="__('Numéro Mobile Money')"
                type="tel"
                required
                autocomplete="tel"
                placeholder="650 00 00 01"
            />

            <flux:button variant="primary" type="submit" data-test="pay-registration-fee">
                <span wire:loading.remove wire:target="payRegistrationFee">
                    {{ __('Payer :amount', ['amount' => $this->registrationFee()->format()]) }}
                </span>
                <span wire:loading wire:target="payRegistrationFee">{{ __('Redirection…') }}</span>
            </flux:button>

            <flux:text class="text-xs">
                {{ __('Vous serez redirigé vers la page de paiement. La file d\'attente doit tourner (`php artisan queue:work`) pour que la confirmation arrive.') }}
            </flux:text>
        </form>
    @elseif ($this->awaitsValidation())
        <flux:callout icon="clock">
            <flux:callout.heading>{{ __('Compte en attente de validation') }}</flux:callout.heading>
            <flux:callout.text>
                {{ __('Vos frais d\'inscription ont bien été reçus. Un administrateur examine votre dossier ; vous serez notifié par e-mail dès qu\'une décision sera prise.') }}
            </flux:callout.text>
        </flux:callout>
    @elseif ($this->wasRejected())
        <flux:callout icon="x-circle" variant="danger">
            <flux:callout.heading>{{ __('Demande refusée') }}</flux:callout.heading>
            <flux:callout.text>
                @if ($this->rejectionReason())
                    {{ __('Motif : :reason', ['reason' => $this->rejectionReason()]) }}
                @else
                    {{ __('Votre demande de compte agriculteur n\'a pas été retenue.') }}
                @endif
            </flux:callout.text>
        </flux:callout>
    @else
        <flux:callout icon="exclamation-triangle" variant="warning">
            <flux:callout.heading>{{ __('Accès restreint') }}</flux:callout.heading>
            <flux:callout.text>
                {{ __('Votre compte n\'est pas actif. Contactez un administrateur pour en connaître la raison.') }}
            </flux:callout.text>
        </flux:callout>
    @endif
</div>

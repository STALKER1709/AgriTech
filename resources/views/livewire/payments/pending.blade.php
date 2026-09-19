<div class="flex flex-col gap-6" @if (! $this->isSettled()) wire:poll.2s="refreshStatus" @endif>
    @if (! $this->isSettled())
        <div class="flex flex-col items-center gap-3 text-center">
            <flux:icon.loading class="size-8" />
            <flux:heading size="xl" level="1">{{ __('Vérification du paiement') }}</flux:heading>
            <flux:text>
                {{ __('Nous attendons la confirmation de l\'opérateur. Cet écran se met à jour tout seul.') }}
            </flux:text>
        </div>

        <flux:callout icon="information-circle">
            <flux:callout.text>
                {{ __('Le paiement n\'est validé que sur confirmation vérifiée côté serveur : revenir sur cette page ne suffit jamais à le faire aboutir.') }}
            </flux:callout.text>
        </flux:callout>
    @elseif ($this->hasSucceeded())
        <div class="flex flex-col items-center gap-3 text-center">
            <flux:icon.check-circle class="size-10 text-green-600" />
            <flux:heading size="xl" level="1">{{ __('Paiement confirmé') }}</flux:heading>
            <flux:text>{{ __('Votre paiement de :amount a bien été reçu.', ['amount' => $payment->amount->format()]) }}</flux:text>
        </div>

        <flux:button variant="primary" :href="$this->continueUrl()" wire:navigate>
            {{ __('Continuer') }}
        </flux:button>
    @else
        <div class="flex flex-col items-center gap-3 text-center">
            <flux:icon.x-circle class="size-10 text-red-600" />
            <flux:heading size="xl" level="1">{{ __('Paiement non abouti') }}</flux:heading>
            <flux:text>
                {{ $payment->status === \App\Enums\PaymentStatus::Expired
                    ? __('Aucune réponse n\'est parvenue dans le délai imparti. Vous pouvez réessayer.')
                    : __('L\'opérateur a refusé le paiement. Vous pouvez réessayer.') }}
            </flux:text>
        </div>

        <flux:button variant="primary" :href="$this->continueUrl()" wire:navigate>
            {{ __('Retour à mon compte') }}
        </flux:button>
    @endif

    <div class="text-center text-xs text-zinc-500">
        {{ __('Référence : :reference', ['reference' => $payment->provider_reference]) }}
    </div>
</div>

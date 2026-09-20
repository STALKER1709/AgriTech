<div class="flex flex-col gap-5" @if (! $this->isSettled()) wire:poll.2s="refreshStatus" @endif>
    @if (! $this->isSettled())
        {{-- Attente : médaillon animé façon écran « Validation OTP USSD » --}}
        <div class="stitch-card flex flex-col items-center gap-4 px-6 py-10 text-center">
            <span class="relative grid size-20 place-items-center rounded-full bg-stitch-gold-soft">
                <flux:icon.clock class="size-9 text-[#8d6b00]" />
                <span class="absolute inset-0 animate-ping rounded-full bg-stitch-gold/30"></span>
            </span>

            <div>
                <h1 class="text-xl font-bold">{{ __('Vérification du paiement') }}</h1>
                <p class="mx-auto mt-1 max-w-sm text-sm text-stitch-muted">
                    {{ __('Nous attendons la confirmation de l\'opérateur. Cet écran se met à jour tout seul.') }}
                </p>
            </div>

            <span class="stitch-badge-gold">{{ __('En attente') }}</span>
        </div>

        <div class="flex items-start gap-2 rounded-xl bg-stitch-low px-4 py-3 text-xs text-stitch-muted">
            <flux:icon.information-circle class="mt-0.5 size-4 shrink-0" />
            {{ __('Le paiement n\'est validé que sur confirmation vérifiée côté serveur : revenir sur cette page ne suffit jamais à le faire aboutir.') }}
        </div>
    @elseif ($this->hasSucceeded())
        {{-- Succès : médaillon vert façon « Paiement confirmé avec succès ! » --}}
        <div class="stitch-card flex flex-col items-center gap-4 px-6 py-10 text-center">
            <span class="grid size-20 place-items-center rounded-full bg-stitch-success-soft">
                <flux:icon.check class="size-9 text-stitch-success" />
            </span>

            <div>
                <h1 class="text-xl font-bold">{{ __('Paiement confirmé') }}</h1>
                <p class="mx-auto mt-1 max-w-sm text-sm text-stitch-muted">
                    {{ __('Votre paiement de :amount a bien été reçu.', ['amount' => $payment->amount->format()]) }}
                </p>
            </div>

            <span class="stitch-badge-success">{{ __('Payé & enregistré') }}</span>
        </div>

        <flux:button variant="primary" :href="$this->continueUrl()" wire:navigate class="w-full sm:w-auto sm:self-center">
            {{ __('Continuer') }}
        </flux:button>
    @else
        {{-- Échec : médaillon rouge façon « Le paiement n'a pas pu aboutir » --}}
        <div class="stitch-card flex flex-col items-center gap-4 px-6 py-10 text-center">
            <span class="grid size-20 place-items-center rounded-full bg-stitch-danger-soft">
                <flux:icon.x-mark class="size-9 text-stitch-danger" />
            </span>

            <div>
                <h1 class="text-xl font-bold">{{ __('Paiement non abouti') }}</h1>
                <p class="mx-auto mt-1 max-w-sm text-sm text-stitch-muted">
                    {{ $payment->status === \App\Enums\PaymentStatus::Expired
                        ? __('Aucune réponse n\'est parvenue dans le délai imparti. Vous pouvez réessayer.')
                        : __('L\'opérateur a refusé le paiement. Vous pouvez réessayer.') }}
                </p>
            </div>

            <span class="stitch-badge-danger">{{ __('Échec') }}</span>
        </div>

        <flux:button variant="primary" :href="$this->continueUrl()" wire:navigate class="w-full sm:w-auto sm:self-center">
            {{ __('Retour à mon compte') }}
        </flux:button>
    @endif

    {{-- Reçu : référence, façon « Reçu électronique » --}}
    <div class="mx-auto flex items-center gap-2 rounded-full border border-stitch-border bg-white px-4 py-2 text-xs text-stitch-muted shadow-card">
        <flux:icon.receipt-percent class="size-4" />
        {{ __('Référence : :reference', ['reference' => $payment->provider_reference]) }}
    </div>
</div>

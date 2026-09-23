<div class="flex w-full max-w-lg mx-auto flex-col gap-space-md"
     @if (! $this->isSettled()) wire:poll.2s="refreshStatus" @endif>

    @php($succeeded = $this->hasSucceeded())
    @php($settled = $this->isSettled())

    {{-- Héros d'état. Trois écrans des maquettes en un : en attente,
         `agritech_paiement_r_ussi`, `agritech_paiement_chou`. --}}
    <section class="relative flex flex-col items-center text-center gap-space-sm pt-space-lg pb-space-md">
        <div class="relative flex items-center justify-center">
            @unless ($settled)
                <span class="absolute w-28 h-28 rounded-full bg-primary/10 animate-ping"></span>
                <span class="absolute w-36 h-36 rounded-full bg-primary/5"></span>
            @endunless

            <span @class([
                'relative w-20 h-20 rounded-full flex items-center justify-center shadow-raised',
                'bg-status-success text-on-primary' => $succeeded,
                'bg-[#ffebee] text-status-error' => $settled && ! $succeeded,
                'bg-primary-fixed text-on-primary-fixed' => ! $settled,
            ])>
                <x-icon :name="$succeeded ? 'check' : ($settled ? 'sentiment_dissatisfied' : 'phonelink_ring')" size="40" />
            </span>
        </div>

        <h1 class="font-headline-lg-mobile text-headline-lg-mobile text-text-primary tracking-tight mt-space-xs">
            @if ($succeeded)
                {{ __('Paiement confirmé') }}
            @elseif ($settled)
                {{ __('Le paiement n\'a pas abouti') }}
            @else
                {{ __('Confirmation en cours') }}
            @endif
        </h1>

        <p class="font-body-md text-body-md text-text-secondary leading-snug max-w-sm">
            @if ($succeeded)
                {{ __('L\'opérateur a confirmé la transaction côté serveur. Votre commande est transmise aux producteurs.') }}
            @elseif ($settled)
                {{ __('Rien n\'a été débité et votre panier n\'a pas bougé. Vous pouvez réessayer quand vous voulez.') }}
            @else
                {{ __('Nous attendons la réponse de l\'opérateur. Cette page se met à jour toute seule ; rien ne se décide dans votre navigateur.') }}
            @endif
        </p>

        @if ($succeeded)
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-status-success/10 text-status-success font-label-sm text-label-sm font-semibold">
                <x-icon name="verified_user" size="16" />
                {{ __('Vérifié par webhook signé') }}
            </span>
        @elseif (! $settled)
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-surface-container text-text-secondary font-label-sm text-label-sm">
                <x-icon name="sync" size="16" class="text-primary" />
                {{ __('Vérification automatique toutes les 2 secondes') }}
            </span>
        @endif
    </section>

    {{-- Récapitulatif de la transaction --}}
    <section class="bg-surface-container-lowest rounded-xl p-space-md shadow-card flex flex-col gap-space-sm">
        <div class="flex items-center justify-between gap-space-sm">
            <span class="inline-flex items-center gap-2 font-label-lg text-label-lg text-text-primary">
                <span @class([
                    'w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold',
                    'bg-payment-mtn text-on-surface' => $payment->method === \App\Enums\PaymentMethod::MtnMomo,
                    'bg-payment-orange text-surface-white' => $payment->method !== \App\Enums\PaymentMethod::MtnMomo,
                ])>{{ $payment->method === \App\Enums\PaymentMethod::MtnMomo ? 'M' : 'O' }}</span>
                {{ $payment->method->label() }}
            </span>

            <x-badge :variant="$succeeded ? 'success' : ($settled ? 'danger' : 'warning')" data-test="payment-status">
                {{ $payment->status->label() }}
            </x-badge>
        </div>

        <div class="h-px bg-surface-container w-full"></div>

        <div class="flex justify-between items-baseline gap-space-sm">
            <span class="font-body-md text-body-md text-text-secondary">{{ __('Montant') }}</span>
            <span class="font-headline-md text-headline-md text-primary" data-test="payment-amount">
                {{ $payment->amount->format() }}
            </span>
        </div>

        <div class="flex justify-between items-center gap-space-sm">
            <span class="font-body-md text-body-md text-text-secondary">{{ __('Objet') }}</span>
            <span class="font-body-md-bold text-body-md-bold text-text-primary">{{ $payment->purpose->label() }}</span>
        </div>

        <div class="flex justify-between items-center gap-space-sm">
            <span class="font-body-md text-body-md text-text-secondary">{{ __('Référence') }}</span>
            <span class="px-2 py-0.5 rounded bg-surface-container font-mono text-label-sm text-on-surface select-all">
                {{ $payment->provider_reference }}
            </span>
        </div>

        @if ($payment->confirmed_at)
            <div class="flex justify-between items-center gap-space-sm">
                <span class="font-body-md text-body-md text-text-secondary">{{ __('Confirmé le') }}</span>
                <span class="font-label-sm text-label-sm text-text-primary">
                    {{ $payment->confirmed_at->timezone(config('app.timezone'))->translatedFormat('d F Y • H:i') }}
                </span>
            </div>
        @endif
    </section>

    {{-- Rappel technique pendant l'attente : sans la file, rien n'arrivera --}}
    @unless ($settled)
        <div class="bg-surface-container rounded-lg p-space-sm flex items-start gap-space-xs">
            <x-icon name="terminal" size="20" class="text-primary shrink-0 mt-0.5" />
            <p class="font-label-sm text-label-sm text-on-surface-variant leading-relaxed">
                {{ __('Le callback de la passerelle passe par la file d\'attente : `php artisan queue:work` doit tourner. Sans réponse, la réconciliation clôt le paiement au bout de :minutes minutes.', [
                    'minutes' => (int) config('payments.expiration_minutes', 15),
                ]) }}
            </p>
        </div>
    @endunless

    {{-- Actions --}}
    <section class="flex flex-col gap-3">
        @if ($succeeded)
            <x-button :href="$this->continueUrl()" :icon="$this->continueAction()['icon']" class="w-full">
                {{ $this->continueAction()['label'] }}
            </x-button>

            <x-button variant="ghost" :href="route('catalog.browse')" icon="storefront" class="w-full">
                {{ __('Retour au catalogue') }}
            </x-button>
        @elseif ($settled)
            <x-button :href="$this->continueUrl()" icon="refresh" class="w-full" data-test="retry">
                {{ __('Réessayer le paiement') }}
            </x-button>

            <x-button variant="ghost" :href="route('catalog.browse')" icon="storefront" class="w-full">
                {{ __('Retour au catalogue') }}
            </x-button>
        @else
            <button type="button" wire:click="refreshStatus" data-test="refresh-status"
                    class="w-full h-12 rounded-full bg-surface-container-lowest hover:bg-surface-container text-primary font-label-lg text-label-lg flex items-center justify-center gap-2 shadow-card active:scale-[0.99] transition-transform cursor-pointer">
                <x-icon name="refresh" size="20" />
                {{ __('Vérifier le statut maintenant') }}
            </button>

            <x-button variant="ghost" :href="$this->continueUrl()" class="w-full">
                {{ __('Revenir en arrière') }}
            </x-button>
        @endif
    </section>
</div>

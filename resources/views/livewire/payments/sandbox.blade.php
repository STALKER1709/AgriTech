<div class="flex flex-col gap-5">
    {{-- Bandeau volontairement voyant : cette page n'existe pas en production. --}}
    <div class="flex items-start gap-3 rounded-xl border-2 border-dashed border-amber-400 bg-amber-50 p-4">
        <flux:icon.exclamation-triangle class="mt-0.5 size-5 shrink-0 text-amber-600" />
        <div>
            <p class="font-display text-sm font-bold text-amber-700">{{ __('Environnement de test') }}</p>
            <p class="text-sm text-amber-700">
                {{ __('Aucun opérateur Mobile Money réel n\'est contacté. Aucun argent ne circule.') }}
            </p>
        </div>
    </div>

    {{-- Carte montant, façon écran « Choix du moyen de paiement » --}}
    <div class="stitch-card flex flex-col gap-4 p-5 sm:p-6">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-2 text-sm text-stitch-muted">
                <flux:icon.lock-closed class="size-4 text-stitch-primary" />
                {{ __('Montant à payer') }}
            </div>
            @if ($payment->method === \App\Enums\PaymentMethod::MtnMomo)
                <span class="grid h-8 place-items-center rounded-lg bg-stitch-mtn px-3 text-[10px] font-black text-black">MTN MoMo</span>
            @else
                <span class="grid h-8 place-items-center rounded-lg bg-stitch-orange px-3 text-[10px] font-black text-white">Orange Money</span>
            @endif
        </div>

        <p class="stitch-price text-center text-4xl">{{ $payment->amount->format() }}</p>

        <dl class="grid gap-2 text-sm">
            <div class="flex justify-between gap-4 border-t border-stitch-high pt-2">
                <dt class="text-stitch-muted">{{ __('Objet') }}</dt>
                <dd class="font-medium">{{ $payment->purpose->label() }}</dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="text-stitch-muted">{{ __('Référence') }}</dt>
                <dd class="font-mono text-xs">{{ $payment->provider_reference }}</dd>
            </div>
        </dl>
    </div>

    <form wire:submit="confirm" class="flex flex-col gap-4">
        <flux:input
            wire:model="phone"
            :label="__('Numéro Mobile Money')"
            :description="__('Deux numéros ont un comportement forcé : 670 00 00 00 échoue toujours, 670 00 00 99 n\'aboutit jamais.')"
            type="tel"
            required
            autocomplete="tel"
            placeholder="650 00 00 01"
        />

        <div class="flex flex-col gap-2">
            <flux:button variant="primary" type="submit" data-test="payment-confirm">
                <span wire:loading.remove wire:target="confirm">{{ __('Confirmer le paiement') }}</span>
                <span wire:loading wire:target="confirm">{{ __('Envoi en cours…') }}</span>
            </flux:button>

            <flux:button variant="danger" type="button" wire:click="refuse" data-test="payment-refuse">
                {{ __('Refuser le paiement') }}
            </flux:button>

            <flux:button variant="ghost" type="button" wire:click="abandon" data-test="payment-abandon">
                {{ __('Laisser expirer') }}
            </flux:button>
        </div>
    </form>

    <flux:callout icon="information-circle">
        <flux:callout.text>
            {{ __('La réponse de l\'opérateur passe par la file d\'attente : `php artisan queue:work` doit tourner pour que le paiement aboutisse.') }}
        </flux:callout.text>
    </flux:callout>
</div>

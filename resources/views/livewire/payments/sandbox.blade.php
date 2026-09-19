<div class="flex flex-col gap-6">
    {{-- Bandeau volontairement voyant : cette page n'existe pas en production. --}}
    <div class="rounded-lg border-2 border-dashed border-amber-500 bg-amber-50 p-4 text-center dark:bg-amber-950/40">
        <flux:heading size="lg" class="text-amber-800 dark:text-amber-200">
            {{ __('Environnement de test') }}
        </flux:heading>
        <flux:text class="mt-1 text-amber-800 dark:text-amber-200">
            {{ __('Aucun opérateur Mobile Money réel n\'est contacté. Aucun argent ne circule.') }}
        </flux:text>
    </div>

    <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
        <flux:text>{{ __('Montant à payer') }}</flux:text>
        <flux:heading size="xl" level="1" class="mt-1">{{ $payment->amount->format() }}</flux:heading>

        <dl class="mt-4 grid gap-2 text-sm">
            <div class="flex justify-between gap-4">
                <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Opérateur') }}</dt>
                <dd>{{ $payment->method->label() }}</dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Objet') }}</dt>
                <dd>{{ $payment->purpose->label() }}</dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Référence') }}</dt>
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

<div class="flex w-full max-w-lg mx-auto flex-col">
    {{-- Bandeau d'avertissement — `agritech_passerelle_de_paiement_de_test` --}}
    <div class="w-full mt-space-sm mb-space-md rounded-xl overflow-hidden shadow-card">
        <div class="h-2 w-full bg-gradient-to-r from-payment-mtn via-secondary-container to-payment-mtn opacity-90"></div>

        <div class="p-space-md flex items-start gap-space-sm bg-surface-container-highest/60 backdrop-blur-sm">
            <div class="w-9 h-9 rounded-full bg-payment-mtn flex items-center justify-center shrink-0 shadow-card">
                <x-icon name="warning" size="20" class="text-on-secondary-fixed" />
            </div>
            <div class="flex flex-col min-w-0">
                <div class="flex items-center gap-space-xs flex-wrap">
                    <span class="font-headline-sm text-label-lg text-on-surface uppercase tracking-wider">
                        {{ __('Environnement de test') }}
                    </span>
                    <span class="px-2 py-0.5 rounded-full bg-payment-mtn/30 text-on-surface font-label-sm text-label-sm">
                        {{ __('SIMULATION') }}
                    </span>
                </div>
                <p class="font-body-md text-label-sm text-text-secondary mt-0.5 leading-relaxed">
                    {{ __('Aucun argent n\'est débité et aucun opérateur n\'est contacté. Cette page remplace la page de paiement que MTN ou Orange hébergerait.') }}
                </p>
            </div>
        </div>
    </div>

    {{-- Carte passerelle --}}
    <div class="w-full bg-surface-container-lowest rounded-xl shadow-raised p-space-md sm:p-space-lg flex flex-col gap-space-md relative overflow-hidden">
        <div class="absolute -right-8 -top-8 w-36 h-36 rounded-full bg-surface-container-low opacity-60 pointer-events-none flex items-center justify-center">
            <x-icon name="payments" size="80" class="text-outline-variant/30" />
        </div>

        <div class="flex items-center justify-between z-10 gap-space-sm">
            <div class="flex items-center gap-space-sm min-w-0">
                <div class="w-10 h-10 rounded-lg bg-primary flex items-center justify-center text-on-primary shadow-card shrink-0">
                    <x-icon name="account_balance" size="22" />
                </div>
                <div class="min-w-0">
                    <h1 class="font-headline-sm text-headline-sm text-on-surface leading-tight">{{ __('Passerelle AgriTech') }}</h1>
                    <span class="font-label-sm text-label-sm text-text-secondary">{{ __('Simulateur Mobile Money') }}</span>
                </div>
            </div>

            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-surface-container text-on-surface-variant font-label-sm text-label-sm shrink-0">
                <span class="w-2 h-2 rounded-full bg-status-warning"></span>
                {{ __('En attente') }}
            </span>
        </div>

        <div class="w-full p-space-md rounded-xl bg-surface-container-low flex flex-col items-center justify-center gap-1 text-center z-10">
            <span class="font-label-sm text-label-sm text-text-secondary uppercase tracking-wider">
                {{ __('Montant de la transaction') }}
            </span>
            <div class="flex items-baseline gap-1.5">
                <span class="font-display-lg-mobile text-display-lg-mobile text-primary font-bold tracking-tight">
                    {{ $payment->amount->formatNumber() }}
                </span>
                <span class="font-headline-sm text-headline-sm text-primary font-semibold">FCFA</span>
            </div>
            <div class="flex items-center gap-1 text-text-secondary font-label-sm text-label-sm mt-0.5">
                <x-icon name="verified_user" size="16" class="text-status-success" />
                {{ __('Aucun frais ajouté') }}
            </div>
        </div>

        <div class="flex flex-col gap-2.5 font-body-md text-label-lg z-10">
            <div class="flex justify-between items-center gap-space-sm py-1">
                <span class="text-text-secondary">{{ __('Objet') }}</span>
                <span class="text-on-surface font-semibold text-right">{{ $payment->purpose->label() }}</span>
            </div>

            <div class="flex justify-between items-center gap-space-sm py-1">
                <span class="text-text-secondary">{{ __('Opérateur') }}</span>
                <div class="flex items-center gap-2">
                    <span @class([
                        'w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold shadow-card',
                        'bg-payment-mtn text-on-surface' => $payment->method === \App\Enums\PaymentMethod::MtnMomo,
                        'bg-payment-orange text-surface-white' => $payment->method !== \App\Enums\PaymentMethod::MtnMomo,
                    ])>{{ $payment->method === \App\Enums\PaymentMethod::MtnMomo ? 'M' : 'O' }}</span>
                    <span class="text-on-surface font-semibold">{{ $payment->method->label() }}</span>
                </div>
            </div>

            <div class="flex justify-between items-center gap-space-sm py-1">
                <span class="text-text-secondary">{{ __('Numéro payeur') }}</span>
                <span class="text-on-surface font-semibold tracking-wide">
                    {{ \App\Support\PhoneNumber::tryParse($phone)?->formatInternational() ?? $phone }}
                </span>
            </div>

            <div class="flex justify-between items-center gap-space-sm py-1">
                <span class="text-text-secondary">{{ __('Référence') }}</span>
                <span class="px-2 py-0.5 rounded bg-surface-container font-mono text-label-sm text-on-surface select-all">
                    {{ $payment->provider_reference }}
                </span>
            </div>

            <div class="flex justify-between items-center gap-space-sm py-1">
                <span class="text-text-secondary">{{ __('Date et heure') }}</span>
                <span class="text-on-surface text-label-sm font-medium">
                    {{ $payment->created_at?->timezone(config('app.timezone'))->translatedFormat('d F Y • H:i') }}
                </span>
            </div>

            <div class="flex justify-between items-center gap-space-sm py-1.5 px-3 rounded-lg bg-status-warning/10">
                <span class="text-status-warning font-semibold flex items-center gap-1.5 text-label-sm">
                    <x-icon name="sync" size="18" />
                    {{ __('Statut actuel') }}
                </span>
                <span class="text-status-warning font-semibold text-label-sm uppercase tracking-wide">
                    {{ $payment->status->label() }}
                </span>
            </div>
        </div>
    </div>

    {{-- Numéro payeur, modifiable avant de décider --}}
    <div class="mt-space-md w-full bg-surface-container-lowest rounded-xl p-space-md shadow-card flex flex-col gap-space-sm">
        <label for="sandbox-phone" class="font-label-lg text-label-lg text-on-surface">
            {{ __('Numéro qui valide la transaction') }}
        </label>

        <div class="flex items-center bg-surface-container-low rounded-xl px-space-sm py-1.5 focus-within:bg-surface-white focus-within:shadow-raised transition-all">
            <span class="font-headline-sm text-headline-sm tracking-tight pr-3 pl-1 text-on-surface select-none">+237</span>
            <div class="h-6 w-0.5 bg-outline-variant mr-3"></div>
            <input id="sandbox-phone" type="tel" wire:model="phone" data-test="phone" placeholder="670 12 34 56"
                   class="w-full bg-transparent font-headline-sm text-headline-sm text-on-surface placeholder:text-outline outline-none tracking-wide" />
        </div>

        @error('phone')
            <p class="flex items-center gap-1 font-label-sm text-label-sm text-status-error">
                <x-icon name="error" size="14" />
                {{ $message }}
            </p>
        @enderror
    </div>

    {{-- Trois issues, comme la maquette --}}
    <div class="mt-space-md w-full bg-surface-container-low rounded-xl p-space-md flex flex-col gap-space-md shadow-card">
        <div class="flex items-center gap-space-xs">
            <div class="w-7 h-7 rounded-full bg-surface-container-highest flex items-center justify-center">
                <x-icon name="science" size="18" class="text-on-surface-variant" />
            </div>
            <h2 class="font-label-lg text-label-lg text-on-surface">{{ __('Choisissez l\'issue à simuler') }}</h2>
        </div>

        <div class="flex flex-col gap-space-sm w-full">
            <button type="button" wire:click="confirm" wire:loading.attr="disabled" data-test="confirm-payment"
                    class="w-full min-h-[52px] rounded-full bg-primary text-on-primary font-headline-sm text-label-lg flex items-center justify-between px-space-md shadow-card active:scale-[0.98] transition-all hover:bg-primary-container cursor-pointer">
                <span class="flex items-center gap-3 min-w-0">
                    <span class="w-8 h-8 rounded-full bg-on-primary/20 flex items-center justify-center shrink-0">
                        <x-icon name="check_circle" size="20" class="text-on-primary" />
                    </span>
                    <span class="flex flex-col text-left min-w-0">
                        <span class="font-semibold leading-tight">{{ __('Simuler un paiement réussi') }}</span>
                        <span class="font-label-sm text-[11px] opacity-85">{{ __('L\'opérateur confirmera par webhook signé') }}</span>
                    </span>
                </span>
                <x-icon name="arrow_forward" size="20" />
            </button>

            <button type="button" wire:click="refuse" wire:loading.attr="disabled" data-test="refuse-payment"
                    class="w-full min-h-[52px] rounded-full bg-error text-on-error font-headline-sm text-label-lg flex items-center justify-between px-space-md shadow-card active:scale-[0.98] transition-all hover:bg-status-error cursor-pointer">
                <span class="flex items-center gap-3 min-w-0">
                    <span class="w-8 h-8 rounded-full bg-on-error/20 flex items-center justify-center shrink-0">
                        <x-icon name="cancel" size="20" class="text-on-error" />
                    </span>
                    <span class="flex flex-col text-left min-w-0">
                        <span class="font-semibold leading-tight">{{ __('Simuler un refus') }}</span>
                        <span class="font-label-sm text-[11px] opacity-85">{{ __('Solde insuffisant ou code refusé') }}</span>
                    </span>
                </span>
                <x-icon name="arrow_forward" size="20" />
            </button>

            <button type="button" wire:click="abandon" data-test="abandon-payment"
                    class="w-full min-h-[52px] rounded-full bg-outline text-surface font-headline-sm text-label-lg flex items-center justify-between px-space-md shadow-card active:scale-[0.98] transition-all hover:bg-on-surface-variant cursor-pointer">
                <span class="flex items-center gap-3 min-w-0">
                    <span class="w-8 h-8 rounded-full bg-surface/20 flex items-center justify-center shrink-0">
                        <x-icon name="hourglass_disabled" size="20" class="text-surface" />
                    </span>
                    <span class="flex flex-col text-left min-w-0">
                        <span class="font-semibold leading-tight">{{ __('Ne pas répondre') }}</span>
                        <span class="font-label-sm text-[11px] opacity-85">
                            {{ __('Expiration au bout de :minutes minutes, par la réconciliation', ['minutes' => (int) config('payments.expiration_minutes', 15)]) }}
                        </span>
                    </span>
                </span>
                <x-icon name="arrow_forward" size="20" />
            </button>
        </div>

        <p class="font-label-sm text-label-sm text-text-secondary leading-relaxed">
            {{ __('Cliquer ici ne change pas le paiement : cela met en file le callback signé de l\'opérateur. `php artisan queue:work` doit tourner pour qu\'il arrive.') }}
        </p>
    </div>
</div>

{{--
    Reproduction de `agritech_compte_en_attente_de_validation` : badge
    illustré avec sa pastille d'état, carte du dossier d'exploitation, frise
    d'avancement en quatre étapes avec sa barre de progression, puis le
    bandeau explicatif.

    Écarts : la maquette affiche une superficie arable, des « filières
    déclarées », un délai de 24 h et la carte d'un auditeur régional nommé.
    Le profil d'exploitation ne porte ni surface ni filières, aucun délai
    n'est garanti et il n'existe pas d'auditeur. Le pourcentage, lui, reste :
    ce n'est pas une estimation mais le compte des étapes acquises.
--}}
@php($user = $this->user())
@php($profile = $user->farmerProfile)

<div class="flex flex-col gap-space-md">
    {{-- Badge d'état --}}
    <section class="flex flex-col items-center text-center gap-space-xs">
        <div class="relative">
            <span class="absolute inset-0 rounded-full bg-primary-fixed blur-2xl opacity-70" aria-hidden="true"></span>
            <span @class([
                'relative flex w-20 h-20 items-center justify-center rounded-full shadow-raised',
                'bg-status-error text-on-error' => $this->wasRejected(),
                'bg-primary text-on-primary' => ! $this->wasRejected(),
            ])>
                <x-icon :name="$this->wasRejected() ? 'gpp_bad' : 'eco'" size="40" filled />
            </span>
        </div>

        <span @class([
            'inline-flex items-center gap-1.5 px-3 py-1 rounded-full font-label-sm text-label-sm mt-1',
            'bg-[#ffebee] text-status-error' => $this->wasRejected(),
            'bg-[#fff3e0] text-status-warning' => ! $this->wasRejected(),
        ])>
            <x-icon :name="$this->wasRejected() ? 'block' : 'hourglass_top'" size="14" />
            {{ $user->status->label() }}
        </span>

        <h1 class="font-headline-lg-mobile text-headline-lg-mobile text-text-primary tracking-tight">
            {{ $this->wasRejected()
                ? __('Votre dossier n\'a pas été retenu')
                : ($this->awaitsPayment() ? __('Une dernière étape') : __('Votre dossier est en vérification')) }}
        </h1>

        <p class="font-body-md text-body-md text-text-secondary max-w-md">
            {{ $this->wasRejected()
                ? __('Vous pouvez corriger ce qui a été signalé et revenir vers nous.')
                : ($this->awaitsPayment()
                    ? __('Les frais d\'inscription réglés, votre exploitation passe en vérification.')
                    : __('Un administrateur examine votre exploitation. Vous serez notifié dès que c\'est fait.')) }}
        </p>
    </section>

    {{-- Motif du refus --}}
    @if ($this->wasRejected() && $this->rejectionReason())
        <section class="rounded-xl bg-[#ffebee] p-space-md flex items-start gap-space-sm" data-test="rejection-reason">
            <x-icon name="report" size="20" class="text-status-error shrink-0 mt-0.5" />
            <div class="min-w-0">
                <span class="font-body-md-bold text-body-md-bold text-status-error block">{{ __('Motif du refus') }}</span>
                <p class="font-body-md text-body-md text-text-primary leading-relaxed">{{ $this->rejectionReason() }}</p>
            </div>
        </section>
    @endif

    {{-- Dossier d'exploitation --}}
    @if ($profile)
        <section class="rounded-2xl bg-surface-container-lowest p-space-md shadow-card flex flex-col gap-space-sm">
            <div class="flex items-center gap-space-xs">
                <x-icon name="agriculture" size="20" class="text-primary" />
                <h2 class="font-headline-sm text-headline-sm text-text-primary">{{ __('Exploitation enregistrée') }}</h2>
            </div>

            <div class="flex flex-col gap-1.5">
                <span class="font-headline-md text-headline-md text-text-primary">{{ $profile->farm_name }}</span>

                <span class="font-label-sm text-label-sm text-text-secondary flex items-center gap-1">
                    <x-icon name="location_on" size="15" class="text-secondary" />
                    {{ collect([$profile->city, $profile->region])->filter()->join(', ') }}
                </span>

                @if ($profile->description)
                    <p class="font-body-md text-body-md text-text-secondary leading-relaxed mt-1">
                        {{ $profile->description }}
                    </p>
                @endif
            </div>

            <a href="{{ route('profile.edit') }}" wire:navigate
               class="mt-1 h-11 px-4 rounded-full bg-surface-container text-primary font-label-lg text-label-lg inline-flex items-center justify-center gap-1.5 hover:bg-surface-container-high transition-colors w-fit">
                <x-icon name="edit_note" size="18" />
                {{ __('Modifier mes informations') }}
            </a>
        </section>
    @endif

    {{-- Avancement --}}
    <section class="rounded-2xl bg-surface-container-lowest p-space-md shadow-card flex flex-col gap-space-md">
        <div class="flex items-center justify-between gap-space-sm">
            <h2 class="font-headline-sm text-headline-sm text-text-primary">{{ __('Avancement du dossier') }}</h2>
            <span class="font-label-lg text-label-lg text-primary font-bold shrink-0" data-test="progress">
                {{ __(':percent % effectué', ['percent' => $this->progress()]) }}
            </span>
        </div>

        <div class="w-full bg-surface-container-highest h-2 rounded-full overflow-hidden">
            <div class="bg-primary h-full rounded-full transition-all duration-500" style="width: {{ $this->progress() }}%;"></div>
        </div>

        <div class="relative pl-6 flex flex-col gap-space-md">
            <div class="absolute left-[11px] top-3 bottom-3 w-[2px] bg-surface-container-highest"></div>

            @foreach ($this->steps() as $step)
                <div @class(['relative flex items-start gap-space-md', 'opacity-55' => $step['state'] === 'todo'])>
                    <span @class([
                        'absolute -left-6 top-0 w-6 h-6 rounded-full flex items-center justify-center shadow-card',
                        'bg-status-success text-on-primary' => $step['state'] === 'done',
                        'bg-tertiary-fixed-dim text-on-tertiary-fixed' => $step['state'] === 'current',
                        'bg-surface-container-highest text-text-secondary' => $step['state'] === 'todo',
                        'bg-status-error text-on-error' => $step['state'] === 'rejected',
                    ])>
                        <x-icon :name="match ($step['state']) {
                            'done' => 'check',
                            'current' => 'sync',
                            'rejected' => 'close',
                            default => 'schedule',
                        }" size="14" />
                    </span>

                    <div @class([
                        'flex flex-col min-w-0 flex-1',
                        'bg-surface-container-low p-space-sm rounded-lg' => $step['state'] === 'current',
                    ])>
                        <div class="flex items-center gap-1.5 min-w-0">
                            <x-icon :name="$step['icon']" size="16" class="text-text-secondary shrink-0" />
                            <span @class([
                                'font-body-md-bold text-body-md-bold',
                                'text-primary' => $step['state'] === 'current',
                                'text-status-error' => $step['state'] === 'rejected',
                                'text-text-primary' => ! in_array($step['state'], ['current', 'rejected'], true),
                            ])>{{ $step['label'] }}</span>
                        </div>

                        <span class="font-label-sm text-label-sm text-text-secondary">{{ $step['detail'] }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Paiement des frais d'inscription --}}
    @if ($this->awaitsPayment())
        <section class="rounded-2xl bg-surface-container-lowest p-space-md shadow-raised flex flex-col gap-space-md">
            <div class="flex items-start justify-between gap-space-sm">
                <div class="min-w-0">
                    <h2 class="font-headline-sm text-headline-sm text-text-primary">{{ __('Frais d\'inscription') }}</h2>
                    <p class="font-label-sm text-label-sm text-text-secondary">
                        {{ __('Réglés une fois, à l\'ouverture du compte.') }}
                    </p>
                </div>

                <span class="font-price-tag text-price-tag text-primary shrink-0" data-test="registration-fee">
                    {{ $this->registrationFee()->format() }}
                </span>
            </div>

            <form wire:submit="payRegistrationFee" class="flex flex-col gap-space-sm">
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach ($this->methods() as $methodOption)
                        <label @class([
                            'relative flex cursor-pointer items-center gap-2.5 rounded-xl p-3 transition-colors',
                            'bg-[#FFF9E6] ring-2 ring-[#CA8A04]' => $method === $methodOption->value && $methodOption === \App\Enums\PaymentMethod::MtnMomo,
                            'bg-[#FFF5ED] ring-2 ring-[#EA580C]' => $method === $methodOption->value && $methodOption === \App\Enums\PaymentMethod::OrangeMoney,
                            'bg-surface-container hover:bg-surface-container-high' => $method !== $methodOption->value,
                        ])>
                            <input type="radio" value="{{ $methodOption->value }}" wire:model.live="method" class="sr-only" data-test="method" />
                            <span @class([
                                'w-9 h-9 shrink-0 rounded-full flex items-center justify-center font-headline-sm text-[12px]',
                                'bg-payment-mtn text-text-primary' => $methodOption === \App\Enums\PaymentMethod::MtnMomo,
                                'bg-payment-orange text-on-primary' => $methodOption === \App\Enums\PaymentMethod::OrangeMoney,
                            ])>{{ $methodOption === \App\Enums\PaymentMethod::MtnMomo ? 'M' : 'O' }}</span>
                            <span class="truncate font-label-lg text-label-lg text-text-primary">{{ $methodOption->label() }}</span>
                        </label>
                    @endforeach
                </div>

                <x-form-field wire="phone" type="tel" :label="__('Numéro du compte payeur')" prefix="+237"
                              required placeholder="670 12 34 56" />

                <button type="submit" data-test="pay-registration-fee" wire:loading.attr="disabled"
                        class="h-14 w-full rounded-full bg-primary text-on-primary font-label-lg text-label-lg flex items-center justify-center gap-2 shadow-raised hover:bg-primary-container transition-colors disabled:opacity-60">
                    <x-icon name="lock" size="20" />
                    <span wire:loading.remove wire:target="payRegistrationFee">
                        {{ __('Payer :amount', ['amount' => $this->registrationFee()->format()]) }}
                    </span>
                    <span wire:loading wire:target="payRegistrationFee">{{ __('Redirection…') }}</span>
                </button>

                <p class="font-label-sm text-label-sm text-text-secondary flex items-start gap-1.5">
                    <x-icon name="shield" size="16" class="text-primary shrink-0 mt-0.5" />
                    {{ __('Paiement Mobile Money simulé. Le compte ne passe en vérification qu\'à la confirmation du serveur, jamais sur un simple retour du navigateur.') }}
                </p>
            </form>
        </section>
    @endif

    {{-- Pourquoi cette vérification --}}
    @unless ($this->wasRejected())
        <section class="rounded-2xl bg-surface-container-low p-space-md flex items-start gap-space-sm">
            <span class="w-10 h-10 rounded-full bg-primary-fixed flex items-center justify-center shrink-0">
                <x-icon name="verified" size="20" class="text-primary" />
            </span>
            <div class="min-w-0">
                <h2 class="font-body-md-bold text-body-md-bold text-text-primary">{{ __('Pourquoi cette vérification ?') }}</h2>
                <p class="font-label-sm text-label-sm text-text-secondary leading-relaxed mt-0.5">
                    {{ __('Les acheteurs commandent auprès de personnes qu\'ils ne rencontrent pas. Vérifier chaque exploitation avant de l\'ouvrir au catalogue est ce qui rend cette confiance possible.') }}
                </p>
            </div>
        </section>
    @endunless

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" data-test="logout"
                class="w-full h-12 rounded-full bg-surface-container-low text-status-error font-label-lg text-label-lg flex items-center justify-center gap-2 hover:bg-surface-container transition-colors">
            <x-icon name="logout" size="18" />
            {{ __('Se déconnecter') }}
        </button>
    </form>
</div>

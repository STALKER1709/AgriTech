{{--
    Reproduction de `agritech_abonnements` : chapeau, carte de la formule en
    cours, cartes de formules empilées avec leurs avantages, rassurance, puis
    le formulaire de paiement.

    Ce que la maquette vend et que la plateforme ne livre pas disparaît :
    certificats officiels, téléchargements hors-ligne, groupes WhatsApp des
    formateurs, consultation téléphonique, support prioritaire 7j/7, accès en
    avant-première, journées de démonstration. Elle annonce aussi un
    prélèvement automatique : aucun abonnement ne se reconduit ici, chacun se
    paie une fois et court jusqu'à son terme.

    Les avantages affichés sont calculés : le nombre réel de formations
    incluses, la durée du plan, son équivalent sur trente jours, et
    l'économie par jour face à la formule la plus chère.
--}}
<div class="flex w-full flex-1 flex-col gap-space-md">
    {{-- Chapeau --}}
    <section class="flex items-center gap-space-sm">
        <span class="flex w-10 h-10 shrink-0 items-center justify-center rounded-full bg-tertiary-fixed text-on-tertiary-fixed-variant">
            <x-icon name="workspace_premium" size="22" />
        </span>
        <div class="min-w-0">
            <h1 class="font-headline-md text-headline-md text-text-primary tracking-tight">{{ __('Abonnements AgriTech') }}</h1>
            <p class="font-label-sm text-label-sm text-text-secondary">
                {{ __('Formations & accompagnement') }}
            </p>
        </div>
    </section>

    {{-- Formule en cours --}}
    @php($current = $this->current())
    @if ($current)
        <section class="rounded-2xl bg-gradient-to-br from-primary via-[#165a31] to-[#0d3f20] text-on-primary p-space-md shadow-raised flex flex-col gap-space-sm relative overflow-hidden"
                 data-test="current-subscription">
            <span class="absolute -right-8 -bottom-8 w-32 h-32 rounded-full bg-tertiary-fixed/15 blur-2xl pointer-events-none" aria-hidden="true"></span>

            <div class="relative z-10 flex items-start justify-between gap-space-sm">
                <div class="min-w-0">
                    <span class="font-label-sm text-label-sm text-on-primary/80 uppercase tracking-wider">{{ __('Votre formule actuelle') }}</span>
                    <h2 class="font-headline-md text-headline-md text-on-primary">{{ $current->plan->name }}</h2>
                </div>

                <span class="shrink-0 inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-tertiary-fixed text-on-tertiary-fixed font-label-sm text-label-sm font-bold">
                    <x-icon name="check_circle" size="14" filled />
                    {{ $current->status->label() }}
                </span>
            </div>

            <div class="relative z-10 flex flex-col gap-1.5">
                <span class="flex items-center gap-1.5 font-label-sm text-label-sm text-on-primary/90">
                    <x-icon name="calendar_clock" size="16" class="text-tertiary-fixed" />
                    {{ __('Valable jusqu\'au :date', [
                        'date' => $current->ends_at?->timezone(config('app.timezone'))->translatedFormat('d F Y'),
                    ]) }}
                </span>

                <span class="flex items-center gap-1.5 font-label-sm text-label-sm text-on-primary/90">
                    <x-icon name="school" size="16" class="text-tertiary-fixed" />
                    {{ trans_choice(
                        ':count formation ouverte par ce Pass|:count formations ouvertes par ce Pass',
                        $this->includedTrainingCount(),
                        ['count' => $this->includedTrainingCount()],
                    ) }}
                </span>

                <span class="flex items-start gap-1.5 font-label-sm text-label-sm text-on-primary/80">
                    <x-icon name="info" size="16" class="text-tertiary-fixed shrink-0 mt-0.5" />
                    {{ __('Aucun prélèvement automatique : le Pass s\'arrête à son terme, et se reprend quand vous le décidez.') }}
                </span>
            </div>

            <a href="{{ route('client.trainings') }}" wire:navigate
               class="relative z-10 h-11 px-5 rounded-full bg-secondary-fixed text-on-secondary-fixed-variant font-label-lg text-label-lg inline-flex items-center justify-center gap-2 shadow-card hover:bg-secondary-fixed-dim transition-colors w-fit">
                <span>{{ __('Voir mes formations') }}</span>
                <x-icon name="arrow_forward" size="18" />
            </a>
        </section>
    @endif

    {{-- Formules --}}
    @if (! $current)
        <section class="flex flex-col gap-space-sm">
            <h2 class="font-headline-sm text-headline-sm text-text-primary">{{ __('Choisir une formule') }}</h2>

            <div class="flex flex-col gap-space-md lg:grid lg:grid-cols-3 lg:gap-gutter">
                @foreach ($this->plans() as $plan)
                    @php($saving = $this->savingsPercent($plan))
                    @php($selected = $selected_plan_id === $plan->id)

                    <article @class([
                        'rounded-2xl p-space-md flex flex-col gap-space-sm transition-shadow',
                        'bg-surface-container-lowest shadow-raised ring-2 ring-primary' => $selected,
                        'bg-surface-container-lowest shadow-card' => ! $selected,
                    ]) data-test="plan">
                        <div class="flex items-start justify-between gap-space-xs">
                            <div class="min-w-0">
                                @if ($saving)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-secondary-fixed text-on-secondary-fixed-variant font-label-sm text-[11px] font-bold mb-1">
                                        <x-icon name="local_fire_department" size="13" />
                                        {{ __('Économisez :percent %', ['percent' => $saving]) }}
                                    </span>
                                @endif

                                <h3 class="font-headline-sm text-headline-sm text-text-primary">{{ $plan->name }}</h3>
                                <p class="font-label-sm text-label-sm text-text-secondary mt-0.5">
                                    {{ $plan->description }}
                                </p>
                            </div>

                            <div class="text-right shrink-0">
                                <div class="font-price-tag text-price-tag text-primary leading-tight">{{ $plan->price->format() }}</div>
                                @if ($plan->duration_days !== 30)
                                    <span class="font-label-sm text-[11px] text-text-secondary">
                                        {{ __('soit :amount / 30 j', ['amount' => $this->monthlyEquivalent($plan)->format()]) }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <ul class="flex flex-col gap-1.5 font-label-sm text-label-sm text-text-primary">
                            @foreach ([
                                ['school', trans_choice(
                                    ':count formation incluse, modules compris|:count formations incluses, modules compris',
                                    $this->includedTrainingCount(),
                                    ['count' => $this->includedTrainingCount()],
                                )],
                                ['calendar_month', trans_choice(':count jour d\'accès|:count jours d\'accès', $plan->duration_days, ['count' => $plan->duration_days])],
                                ['published_with_changes', __('Sans reconduction automatique')],
                                ['smartphone', __('Paiement Mobile Money simulé')],
                            ] as [$icon, $line])
                                <li class="flex items-start gap-2">
                                    <x-icon :name="$icon" size="18" class="text-primary shrink-0 mt-0.5" />
                                    <span>{{ $line }}</span>
                                </li>
                            @endforeach
                        </ul>

                        <button type="button" wire:click="selectPlan({{ $plan->id }})"
                                @class([
                                    'mt-auto h-12 w-full rounded-full font-label-lg text-label-lg flex items-center justify-center gap-2 shadow-card transition-colors',
                                    'bg-primary text-on-primary hover:bg-primary-container' => ! $selected,
                                    'bg-primary-fixed text-on-primary-fixed' => $selected,
                                ])
                                data-test="choose-plan">
                            <span>{{ $selected ? __('Formule choisie') : __('Choisir :plan', ['plan' => $plan->name]) }}</span>
                            <x-icon :name="$selected ? 'check' : 'arrow_forward'" size="18" />
                        </button>
                    </article>
                @endforeach
            </div>
        </section>

        {{-- Rassurance --}}
        <section class="grid grid-cols-1 sm:grid-cols-2 gap-space-sm">
            @foreach ([
                ['lock', __('Paiement mobile vérifié'), __('Le Pass ne s\'ouvre qu\'à la confirmation du serveur, jamais sur un retour du navigateur.')],
                ['published_with_changes', __('Aucune reconduction'), __('Rien n\'est prélevé automatiquement : la formule s\'arrête à son terme.')],
            ] as [$icon, $title, $text])
                <div class="rounded-xl bg-surface-container-low p-space-md flex items-start gap-space-sm">
                    <span class="w-9 h-9 rounded-full bg-primary-fixed flex items-center justify-center shrink-0">
                        <x-icon :name="$icon" size="18" class="text-primary" />
                    </span>
                    <div class="min-w-0">
                        <span class="font-body-md-bold text-body-md-bold text-text-primary block">{{ $title }}</span>
                        <p class="font-label-sm text-label-sm text-text-secondary">{{ $text }}</p>
                    </div>
                </div>
            @endforeach
        </section>

        {{-- Paiement --}}
        @if ($showPlans)
            <form wire:submit="subscribe" class="rounded-2xl bg-surface-container-lowest p-space-md shadow-card flex flex-col gap-space-sm"
                  data-test="subscribe-form">
                <div class="flex items-center gap-space-sm">
                    <span class="w-9 h-9 rounded-full bg-surface-container flex items-center justify-center shrink-0">
                        <x-icon name="lock" size="18" class="text-primary" />
                    </span>
                    <div class="min-w-0">
                        <h2 class="font-headline-sm text-headline-sm text-text-primary">{{ __('Confirmer l\'offre') }}</h2>
                        <p class="font-label-sm text-label-sm text-text-secondary">
                            {{ __('Le délai ne démarre qu\'à la confirmation du paiement : aucun jour n\'est perdu.') }}
                        </p>
                    </div>
                </div>

                @error('selected_plan_id')
                    <p class="flex items-center gap-1 font-label-sm text-label-sm text-status-error">
                        <x-icon name="error" size="14" />
                        {{ $message }}
                    </p>
                @enderror

                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach (\App\Enums\PaymentMethod::cases() as $methodOption)
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

                <div class="flex flex-col gap-1.5">
                    <label for="subscription-phone" class="font-label-lg text-label-lg text-text-secondary">
                        {{ __('Numéro du compte payeur') }}
                    </label>

                    <div class="flex items-center bg-surface-container-low rounded-xl px-space-sm py-1.5 focus-within:bg-surface-white focus-within:shadow-raised transition-all">
                        <span class="font-headline-sm text-headline-sm tracking-tight pr-3 pl-1 text-on-surface select-none">+237</span>
                        <span class="h-6 w-0.5 bg-outline-variant mr-3"></span>
                        <input id="subscription-phone" type="tel" wire:model="phone" data-test="phone"
                               placeholder="670 12 34 56"
                               class="w-full bg-transparent font-headline-sm text-headline-sm text-on-surface placeholder:text-outline outline-none tracking-wide" />
                    </div>

                    @error('phone')
                        <p class="flex items-center gap-1 font-label-sm text-label-sm text-status-error">
                            <x-icon name="error" size="14" />
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <button type="submit" wire:loading.attr="disabled" data-test="subscribe"
                        class="h-14 w-full rounded-full bg-primary text-on-primary font-label-lg text-label-lg flex items-center justify-center gap-2 shadow-raised hover:bg-primary-container transition-colors disabled:opacity-60">
                    <x-icon name="lock" size="20" />
                    {{ __('Souscrire') }}
                </button>
            </form>
        @endif
    @endif
</div>

<div class="flex w-full flex-1 flex-col gap-5">
    {{-- En-tête façon écran Stitch « Abonnements » --}}
    <div class="flex items-center gap-3">
        <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-stitch-gold-soft text-[#8d6b00]">
            <flux:icon.trophy class="size-5" />
        </span>
        <div>
            <h1 class="text-xl font-bold">{{ __('Mon abonnement') }}</h1>
            <p class="text-sm text-stitch-muted">
                {{ __('Un abonnement actif ouvre toutes les formations marquées « incluse dans l\'abonnement ». Paiement Mobile Money simulé.') }}
            </p>
        </div>
    </div>

    {{-- Formule actuelle : carte or façon « Votre formule actuelle » --}}
    @if ($this->current())
        <div class="flex flex-col gap-3 rounded-xl border-2 border-stitch-gold/60 bg-[#fdf6e3] p-4 shadow-card sm:p-5" data-test="current-subscription">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-stitch-gold text-stitch-gold-ink">
                        <flux:icon.clock class="size-5" />
                    </span>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-[#8d6b00]">{{ __('Votre formule actuelle') }}</p>
                        <flux:heading size="lg">{{ $this->current()->plan->name }}</flux:heading>
                        <p class="mt-0.5 text-sm text-stitch-muted">
                            {{ __('Actif depuis le :start, jusqu\'au :end.', [
                                'start' => $this->current()->starts_at?->timezone(config('app.timezone'))->translatedFormat('d F Y'),
                                'end' => $this->current()->ends_at?->timezone(config('app.timezone'))->translatedFormat('d F Y'),
                            ]) }}
                        </p>
                    </div>
                </div>

                <span class="stitch-badge-success">{{ $this->current()->status->label() }}</span>
            </div>

            <p class="flex items-start gap-2 text-xs text-stitch-muted">
                <flux:icon.information-circle class="mt-0.5 size-4 shrink-0" />
                {{ __('Votre abonnement est en cours : il sera renouvelable une fois son terme passé.') }}
            </p>
        </div>
    @else
        <div class="stitch-card flex flex-col items-center gap-3 px-6 py-10 text-center">
            <span class="flex size-14 items-center justify-center rounded-full bg-stitch-gold-soft">
                <flux:icon.sparkles class="size-7 text-[#8d6b00]" />
            </span>
            <h2 class="font-display text-lg font-bold">{{ __('Aucun abonnement actif') }}</h2>
            <p class="max-w-sm text-sm text-stitch-muted">{{ __('Choisissez un plan pour accéder aux formations incluses.') }}</p>
            <flux:button size="sm" variant="primary" wire:click="$set('showPlans', true)" data-test="choose-plan" class="mt-1">
                {{ __('Choisir un plan') }}
            </flux:button>
        </div>
    @endif

    @if (! $this->current())
        <div class="flex flex-col gap-4">
            <h2 class="text-sm font-bold uppercase tracking-wide text-stitch-muted">{{ __('Plans disponibles') }}</h2>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($this->plans() as $plan)
                    <div class="stitch-card flex flex-col gap-2 p-4 {{ $selected_plan_id === $plan->id ? 'border-2 !border-stitch-primary ring-2 ring-stitch-primary/15' : '' }}">
                        <flux:heading size="sm">{{ $plan->name }}</flux:heading>

                        @if ($plan->description)
                            <p class="text-sm text-stitch-muted">{{ $plan->description }}</p>
                        @endif

                        <p class="stitch-price mt-1 text-xl">{{ $plan->price->format() }}</p>
                        <p class="text-xs text-stitch-muted">
                            {{ trans_choice('{1}:count jour|[2,*]:count jours', $plan->duration_days, ['count' => $plan->duration_days]) }}
                        </p>

                        <div class="mt-auto pt-2">
                            <flux:button size="sm" variant="primary" wire:click="selectPlan({{ $plan->id }})" class="w-full">
                                {{ __('Souscrire') }}
                            </flux:button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        @if ($showPlans)
            {{-- Paiement : cartes opérateur façon « Choix du moyen de paiement » --}}
            <form wire:submit="subscribe" class="stitch-card flex flex-col gap-4 p-4 sm:p-5" data-test="subscribe-form">
                <div class="flex items-center gap-2">
                    <span class="flex size-8 items-center justify-center rounded-full bg-stitch-low">
                        <flux:icon.lock-closed class="size-4 text-stitch-primary" />
                    </span>
                    <div>
                        <h2 class="font-display text-sm font-bold">{{ __('Payer l\'abonnement') }}</h2>
                        <p class="text-xs text-stitch-muted">
                            {{ __('Le délai ne démarre qu\'à la confirmation du paiement : aucun jour n\'est perdu.') }}
                        </p>
                    </div>
                </div>

                <div>
                    <flux:label>{{ __('Opérateur') }}</flux:label>
                    <div class="mt-2 grid gap-2 sm:grid-cols-2">
                        @foreach (App\Enums\PaymentMethod::cases() as $methodOption)
                            <label class="relative flex cursor-pointer items-center gap-3 rounded-xl border-2 bg-white p-3 shadow-card transition
                                          {{ $method === $methodOption->value ? 'border-stitch-primary ring-2 ring-stitch-primary/15' : 'border-stitch-border hover:border-stitch-high' }}">
                                <input type="radio" value="{{ $methodOption->value }}" wire:model="method" class="sr-only" data-test="method" />

                                @if ($methodOption->value === 'mtn_momo')
                                    <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-stitch-mtn text-[10px] font-black text-black">MTN</span>
                                @else
                                    <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-stitch-orange text-[10px] font-black text-white">OM</span>
                                @endif

                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-bold">{{ $methodOption->label() }}</span>
                                    <span class="block text-xs text-stitch-muted">*126# / #150#</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <flux:input wire:model="phone" :label="__('Numéro de téléphone')" placeholder="+237 6XX XX XX XX" data-test="phone" />

                <flux:button type="submit" variant="primary" icon="credit-card" wire:loading.attr="disabled" data-test="subscribe">
                    {{ __('Souscrire') }}
                </flux:button>
            </form>
        @endif
    @endif
</div>

<div class="flex w-full flex-1 flex-col gap-6">
    <div>
        <flux:heading size="xl" level="1">{{ __('Mon abonnement') }}</flux:heading>
        <flux:text class="mt-2">
            {{ __('Un abonnement actif ouvre toutes les formations marquées « incluse dans l\'abonnement ». Paiement Mobile Money simulé.') }}
        </flux:text>
    </div>

    @if ($this->current())
        <div class="flex flex-col gap-3 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700" data-test="current-subscription">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <flux:heading size="sm">{{ $this->current()->plan->name }}</flux:heading>
                    <flux:text class="mt-1 text-sm">
                        {{ __('Actif depuis le :start, jusqu\'au :end.', [
                            'start' => $this->current()->starts_at?->timezone(config('app.timezone'))->translatedFormat('d F Y'),
                            'end' => $this->current()->ends_at?->timezone(config('app.timezone'))->translatedFormat('d F Y'),
                        ]) }}
                    </flux:text>
                </div>

                <flux:badge variant="success">{{ $this->current()->status->label() }}</flux:badge>
            </div>

            <flux:callout icon="information-circle">
                <flux:callout.text>
                    {{ __('Votre abonnement est en cours : il sera renouvelable une fois son terme passé.') }}
                </flux:callout.text>
            </flux:callout>
        </div>
    @else
        <flux:callout icon="sparkles">
            <flux:callout.heading>{{ __('Aucun abonnement actif') }}</flux:callout.heading>
            <flux:callout.text>{{ __('Choisissez un plan pour accéder aux formations incluses.') }}</flux:callout.text>
            <x-slot name="actions">
                <flux:button size="sm" variant="primary" wire:click="$set('showPlans', true)" data-test="choose-plan">
                    {{ __('Choisir un plan') }}
                </flux:button>
            </x-slot>
        </flux:callout>
    @endif

    @if (! $this->current())
        <div class="flex flex-col gap-4">
            <flux:heading size="sm">{{ __('Plans disponibles') }}</flux:heading>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($this->plans() as $plan)
                    <div class="flex flex-col gap-2 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700
                                {{ $selected_plan_id === $plan->id ? 'border-accent ring-2 ring-accent/20' : '' }}">
                        <flux:heading size="sm">{{ $plan->name }}</flux:heading>

                        @if ($plan->description)
                            <flux:text class="text-sm">{{ $plan->description }}</flux:text>
                        @endif

                        <flux:heading class="mt-1">{{ $plan->price->format() }}</flux:heading>
                        <flux:text class="text-xs text-zinc-500">
                            {{ trans_choice('{1}:count jour|[2,*]:count jours', $plan->duration_days, ['count' => $plan->duration_days]) }}
                        </flux:text>

                        <div class="mt-auto pt-2">
                            <flux:button size="sm" variant="primary" wire:click="selectPlan({{ $plan->id }})">
                                {{ __('Souscrire') }}
                            </flux:button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        @if ($showPlans)
            <form wire:submit="subscribe" class="flex flex-col gap-4 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700" data-test="subscribe-form">
                <div>
                    <flux:heading size="sm">{{ __('Payer l\'abonnement') }}</flux:heading>
                    <flux:text class="mt-1 text-sm">
                        {{ __('Le délai ne démarre qu\'à la confirmation du paiement : aucun jour n\'est perdu.') }}
                    </flux:text>
                </div>

                <flux:select wire:model="method" :label="__('Opérateur')" data-test="method">
                    @foreach (App\Enums\PaymentMethod::cases() as $methodOption)
                        <flux:select.option value="{{ $methodOption->value }}">{{ $methodOption->label() }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="phone" :label="__('Numéro de téléphone')" placeholder="+237 6XX XX XX XX" data-test="phone" />

                <flux:button type="submit" variant="primary" icon="credit-card" wire:loading.attr="disabled" data-test="subscribe">
                    {{ __('Souscrire') }}
                </flux:button>
            </form>
        @endif
    @endif
</div>

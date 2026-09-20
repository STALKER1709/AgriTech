<div class="flex w-full flex-col gap-6 py-6">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item :href="route('trainings.index')" wire:navigate>{{ __('Formations') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $training->title }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="flex flex-col gap-3">
            <div class="flex aspect-video w-full items-center justify-center rounded-xl bg-zinc-100 dark:bg-zinc-800">
                <flux:icon.academic-cap class="size-12 text-zinc-400" />
            </div>

            <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                <flux:text class="text-sm">{{ __('Proposée par') }}</flux:text>
                <flux:heading size="sm" class="mt-1">{{ $training->farmer->farmerProfile?->farm_name }}</flux:heading>
                <flux:text class="mt-1 text-sm">
                    {{ $training->farmer->farmerProfile?->city }}, {{ $training->farmer->farmerProfile?->region }}
                </flux:text>
            </div>
        </div>

        <div class="flex flex-col gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <flux:badge size="sm">{{ $training->format->label() }}</flux:badge>

                    @if ($training->included_in_subscription)
                        <flux:badge size="sm" variant="lime">{{ __('Incluse dans l\'abonnement') }}</flux:badge>
                    @endif
                </div>

                <flux:heading size="xl" level="1" class="mt-2">{{ $training->title }}</flux:heading>
            </div>

            <flux:heading size="xl" data-test="training-price">{{ $training->price->format() }}</flux:heading>

            <div>
                <flux:heading size="sm">{{ __('Description') }}</flux:heading>
                <flux:text class="mt-2 whitespace-pre-line">{{ $training->description }}</flux:text>
            </div>

            <flux:separator />

            {{-- The module titles are shown to everyone; the files behind them
                 are only ever served by the entitlement-checked controller. --}}
            <div>
                <flux:heading size="sm">{{ __('Contenu de la formation') }}</flux:heading>

                <div class="mt-2 flex flex-col gap-2">
                    @forelse ($training->contents as $content)
                        <div class="flex items-center justify-between gap-3 rounded-lg border border-neutral-200 px-3 py-2 dark:border-neutral-700">
                            <div class="flex min-w-0 items-center gap-2">
                                @if ($content->type->value === 'pdf')
                                    <flux:icon.document-text class="size-5 shrink-0 text-zinc-400" />
                                @else
                                    <flux:icon.video-camera class="size-5 shrink-0 text-zinc-400" />
                                @endif

                                <flux:text class="min-w-0 truncate">{{ $content->title }}</flux:text>
                            </div>

                            @if ($this->hasAccess())
                                <flux:button size="xs" variant="primary" :href="route('trainings.content', ['content' => $content->id])" target="_blank">
                                    {{ __('Ouvrir') }}
                                </flux:button>
                            @else
                                <flux:icon.lock-closed class="size-4 shrink-0 text-zinc-400" />
                            @endif
                        </div>
                    @empty
                        <flux:text class="text-sm text-zinc-500">{{ __('Le contenu sera publié prochainement.') }}</flux:text>
                    @endforelse
                </div>
            </div>

            @if ($this->hasAccess())
                <flux:callout icon="check-circle" variant="success">
                    <flux:callout.text>{{ __('Vous avez accès à cette formation.') }}</flux:callout.text>
                </flux:callout>
            @elseif ($this->canBuy())
                <div class="flex flex-col gap-4 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                    <div>
                        <flux:heading size="sm">{{ __('Acheter la formation') }}</flux:heading>
                        <flux:text class="mt-1 text-sm">
                            {{ __('Paiement Mobile Money simulé. Aucun opérateur réel n\'est contacté.') }}
                        </flux:text>
                    </div>

                    <form wire:submit="buy" class="flex flex-col gap-4">
                        <flux:select wire:model="method" :label="__('Opérateur')" data-test="method">
                            @foreach (App\Enums\PaymentMethod::cases() as $methodOption)
                                <flux:select.option value="{{ $methodOption->value }}">{{ $methodOption->label() }}</flux:select.option>
                            @endforeach
                        </flux:select>

                        <flux:input wire:model="phone" :label="__('Numéro de téléphone')" placeholder="+237 6XX XX XX XX" data-test="phone" />

                        <flux:button type="submit" variant="primary" icon="credit-card" wire:loading.attr="disabled" data-test="buy">
                            {{ __('Acheter :amount', ['amount' => $training->price->format()]) }}
                        </flux:button>
                    </form>
                </div>
            @elseif ($this->isVisitor())
                <flux:callout icon="academic-cap">
                    <flux:callout.text>
                        {{ __('Connectez-vous avec un compte client pour acheter cette formation.') }}
                    </flux:callout.text>
                    <x-slot name="actions">
                        <flux:button size="sm" variant="primary" :href="route('login')">
                            {{ __('Se connecter') }}
                        </flux:button>
                    </x-slot>
                </flux:callout>
            @elseif (! auth()->user()?->hasActiveSubscription() && $training->included_in_subscription)
                <flux:callout icon="sparkles">
                    <flux:callout.text>
                        {{ __('Cette formation est incluse dans l\'abonnement : souscrivez pour y accéder sans l\'acheter.') }}
                    </flux:callout.text>
                    <x-slot name="actions">
                        <flux:button size="sm" variant="primary" :href="route('client.subscriptions')">
                            {{ __('Voir l\'abonnement') }}
                        </flux:button>
                    </x-slot>
                </flux:callout>
            @else
                <flux:callout icon="information-circle">
                    <flux:callout.text>
                        {{ __('Seul un compte client actif peut acheter une formation.') }}
                    </flux:callout.text>
                </flux:callout>
            @endif
        </div>
    </div>
</div>

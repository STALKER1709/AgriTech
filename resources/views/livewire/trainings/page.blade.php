<div class="flex w-full flex-col gap-5 py-2">
    {{-- Breadcrumb row --}}
    <div class="flex items-center justify-between gap-2">
        <a href="{{ route('trainings.index') }}" wire:navigate
           class="inline-flex items-center gap-1.5 rounded-full py-1.5 text-sm font-semibold text-stitch-primary transition-opacity hover:opacity-80">
            <flux:icon.arrow-left class="size-4" />
            {{ __('Retour aux formations') }}
        </a>
    </div>

    <div class="grid gap-6 lg:grid-cols-2 lg:gap-10">
        {{-- Video preview area, gradient like the Stitch screen. --}}
        <div class="flex flex-col gap-3">
            <div class="relative aspect-video w-full overflow-hidden rounded-2xl bg-gradient-to-br from-stitch-primary/20 via-stitch-gold-soft/25 to-stitch-terra-soft/40 shadow-raised">
                <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-black/20" aria-hidden="true"></div>

                <div class="absolute inset-0 flex items-center justify-center">
                    <span class="flex size-16 items-center justify-center rounded-full bg-stitch-primary/90 text-white shadow-float backdrop-blur-sm">
                        <flux:icon.play class="size-7" />
                    </span>
                </div>

                <span class="absolute left-3 top-3 inline-flex items-center gap-1 rounded-full bg-black/60 px-2.5 py-1 text-xs font-semibold text-white backdrop-blur-md">
                    @if ($training->format->value === 'pdf')
                        <flux:icon.document-text class="size-3.5 text-stitch-gold-soft" />
                    @else
                        <flux:icon.video-camera class="size-3.5 text-stitch-gold-soft" />
                    @endif
                    {{ $training->format->label() }}
                </span>

                @if ($training->included_in_subscription)
                    <span class="stitch-badge-gold absolute bottom-3 left-3 shadow-sm">
                        {{ __('Incluse dans l\'abonnement') }}
                    </span>
                @endif
            </div>

            {{-- Instructor card --}}
            <div class="rounded-2xl bg-stitch-low p-4 shadow-card">
                <div class="flex items-center gap-3">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-stitch-primary text-white shadow-card">
                        <flux:icon.leaf class="size-5" />
                    </span>
                    <div class="min-w-0">
                        <flux:heading size="sm" class="truncate">{{ $training->farmer->farmerProfile?->farm_name }}</flux:heading>
                        <p class="flex items-center gap-1 truncate text-xs text-stitch-muted">
                            <flux:icon.map-pin class="size-3.5 shrink-0" />
                            {{ $training->farmer->farmerProfile?->city }}, {{ $training->farmer->farmerProfile?->region }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Detail column --}}
        <div class="flex flex-col gap-4">
            <div class="flex flex-col gap-1">
                <h1 class="font-display text-2xl font-bold tracking-tight text-stitch-ink sm:text-3xl">
                    {{ $training->title }}
                </h1>

                <div class="mt-2 flex items-baseline justify-between gap-3 rounded-2xl bg-stitch-low p-3">
                    <span class="stitch-price text-2xl sm:text-3xl" data-test="training-price">
                        {{ $training->price->format() }}
                    </span>
                    @if ($training->included_in_subscription)
                        <span class="stitch-badge-gold">{{ __('Abonnement') }}</span>
                    @endif
                </div>
            </div>

            <div>
                <flux:heading size="sm">{{ __('Description') }}</flux:heading>
                <flux:text class="mt-2 whitespace-pre-line leading-relaxed">{{ $training->description }}</flux:text>
            </div>

            <flux:separator />

            {{-- The module titles are shown to everyone; the files behind them
                 are only ever served by the entitlement-checked controller. --}}
            <div class="flex flex-col gap-2">
                <flux:heading size="sm">{{ __('Contenu de la formation') }}</flux:heading>

                @forelse ($training->contents as $content)
                    <div class="flex items-center justify-between gap-3 rounded-xl border border-stitch-border bg-white px-3.5 py-2.5 shadow-card">
                        <div class="flex min-w-0 items-center gap-2.5">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-full {{ $content->type->value === 'pdf' ? 'bg-stitch-terra-soft text-stitch-terra-ink' : 'bg-stitch-primary/10 text-stitch-primary' }}">
                                @if ($content->type->value === 'pdf')
                                    <flux:icon.document-text class="size-4" />
                                @else
                                    <flux:icon.video-camera class="size-4" />
                                @endif
                            </span>
                            <flux:text class="min-w-0 truncate font-medium">{{ $content->title }}</flux:text>
                        </div>

                        @if ($this->hasAccess())
                            <flux:button size="xs" variant="primary" class="rounded-full" :href="route('trainings.content', ['content' => $content->id])" target="_blank">
                                {{ __('Ouvrir') }}
                            </flux:button>
                        @else
                            <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-stitch-low text-stitch-muted">
                                <flux:icon.lock-closed class="size-4" />
                            </span>
                        @endif
                    </div>
                @empty
                    <flux:text class="text-sm text-stitch-muted">{{ __('Le contenu sera publié prochainement.') }}</flux:text>
                @endforelse
            </div>

            @if ($this->hasAccess())
                <flux:callout icon="check-circle" variant="success">
                    <flux:callout.text>{{ __('Vous avez accès à cette formation.') }}</flux:callout.text>
                </flux:callout>
            @elseif ($this->canBuy())
                <div class="flex flex-col gap-4 rounded-2xl border border-stitch-border bg-white p-4 shadow-card">
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

                        <flux:input wire:model="phone" :label="__('Numéro de téléphone')" placeholder="6XX XX XX XX" data-test="phone" />

                        <button type="submit"
                                class="flex h-12 w-full items-center justify-center gap-2 rounded-full bg-stitch-terra text-base font-bold text-white shadow-raised transition-colors hover:bg-[#a85c1f] active:scale-[0.99]"
                                data-test="buy">
                            <flux:icon.credit-card class="size-5" />
                            {{ __('Acheter :amount', ['amount' => $training->price->format()]) }}
                        </button>
                    </form>
                </div>
            @elseif ($this->isVisitor())
                <div class="rounded-2xl bg-stitch-low p-4 text-center">
                    <flux:text class="text-sm">
                        {{ __('Connectez-vous avec un compte client pour acheter cette formation.') }}
                    </flux:text>
                    <flux:button size="sm" variant="primary" :href="route('login')" class="mt-3 rounded-full">
                        {{ __('Se connecter') }}
                    </flux:button>
                </div>
            @elseif (! auth()->user()?->hasActiveSubscription() && $training->included_in_subscription)
                <flux:callout icon="sparkles" variant="warning">
                    <flux:callout.text>
                        {{ __('Cette formation est incluse dans l\'abonnement : souscrivez pour y accéder sans l\'acheter.') }}
                    </flux:callout.text>
                    <x-slot name="actions">
                        <flux:button size="sm" variant="primary" :href="route('client.subscriptions')" class="rounded-full">
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

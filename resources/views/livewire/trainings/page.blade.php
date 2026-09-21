<div class="flex w-full flex-col gap-5 py-2">
    {{-- Back breadcrumb, as in the Stitch fiche_formation screen. --}}
    <div class="flex items-center justify-between gap-3">
        <a href="{{ route('trainings.index') }}" wire:navigate
           class="inline-flex items-center gap-1.5 text-sm font-semibold text-stitch-muted transition-colors hover:text-stitch-primary">
            <flux:icon.arrow-left class="size-4" />
            {{ __('Retour aux formations') }}
        </a>

        @if ($training->included_in_subscription)
            <span class="stitch-badge-gold">{{ __('Incluse avec abonnement') }}</span>
        @endif
    </div>

    <div class="grid gap-6 lg:grid-cols-2 lg:gap-10">
        {{-- Video preview area with the cover drawn by the seeder. --}}
        <div class="flex flex-col gap-3">
            <div class="relative aspect-video w-full overflow-hidden rounded-2xl bg-gradient-to-br from-stitch-primary/20 via-stitch-gold-soft/25 to-stitch-terra-soft/40 shadow-raised">
                @if ($training->hasCover())
                    <img src="{{ $training->coverUrl() }}" alt="{{ $training->title }}" class="absolute inset-0 size-full object-cover" />
                    <div class="absolute inset-0 bg-gradient-to-t from-black/55 via-black/10 to-black/25" aria-hidden="true"></div>
                @else
                    <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-black/20" aria-hidden="true"></div>
                @endif

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

                {{-- Module count chip, from the design (« 12 modules »). --}}
                @if ($training->contents->isNotEmpty())
                    <span class="absolute right-3 top-3 inline-flex items-center gap-1 rounded-full bg-black/60 px-2.5 py-1 text-xs font-semibold text-white backdrop-blur-md">
                        <flux:icon.queue-list class="size-3.5 text-stitch-gold-soft" />
                        {{ trans_choice('{1}:count module|[2,*]:count modules', $training->contents->count(), ['count' => $training->contents->count()]) }}
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
                    <span class="inline-flex items-center gap-1 rounded-full bg-white px-2.5 py-0.5 text-xs font-semibold text-stitch-primary shadow-card">
                        <flux:icon.shield-check class="size-3.5" />
                        {{ __('Accès vérifié RG05') }}
                    </span>
                </div>
            </div>

            <div>
                <flux:heading size="sm">{{ __('Description de la formation') }}</flux:heading>
                <flux:text class="mt-2 whitespace-pre-line leading-relaxed">{{ $training->description }}</flux:text>
            </div>

            <flux:separator />

            {{-- Two purchase options side by side, faithful to the Stitch
                 screen: the subscription Pass first, then the one-time buy. --}}
            @if ($this->hasAccess())
                <div class="flex items-center gap-3 rounded-xl border border-stitch-border bg-stitch-success-soft px-4 py-3">
                    <flux:icon.check-circle class="size-5 shrink-0 text-stitch-success" />
                    <p class="text-sm font-medium text-stitch-success">{{ __('Vous avez accès à cette formation.') }}</p>
                </div>
            @elseif ($this->canBuy())
                <div class="flex flex-col gap-3 rounded-2xl border border-stitch-border bg-white p-4 shadow-card">
                    <flux:heading size="sm">{{ __('Accéder à la formation') }}</flux:heading>

                    @if ($training->included_in_subscription)
                        <a href="{{ route('client.subscriptions') }}" wire:navigate
                           class="flex items-center justify-between gap-3 rounded-xl border-2 border-stitch-gold/60 bg-[#fdf6e3] p-3.5 transition hover:shadow-card">
                            <span class="flex min-w-0 items-center gap-3">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-stitch-gold text-stitch-gold-ink">
                                    <flux:icon.trophy class="size-5" />
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-sm font-bold">{{ __('Option 1 — Pass Formations') }}</span>
                                    <span class="block truncate text-xs text-stitch-muted">{{ __('Accès illimité aux formations incluses') }}</span>
                                </span>
                            </span>
                            <flux:icon.chevron-right class="size-4 shrink-0 text-stitch-muted" />
                        </a>
                    @endif

                    <div class="rounded-xl border border-stitch-border bg-white p-3.5">
                        <p class="text-sm font-bold">
                            {{ $training->included_in_subscription ? __('Option 2 — Achat à l\'unité') : __('Achat à l\'unité') }}
                        </p>
                        <p class="mt-0.5 text-xs text-stitch-muted">
                            {{ __('Paiement Mobile Money simulé. Aucun opérateur réel n\'est contacté.') }}
                        </p>

                        <form wire:submit="buy" class="mt-3 flex flex-col gap-3">
                            <div>
                                <flux:label>{{ __('Opérateur') }}</flux:label>
                                <div class="mt-1.5 grid gap-2 sm:grid-cols-2">
                                    @foreach (App\Enums\PaymentMethod::cases() as $methodOption)
                                        <label class="relative flex cursor-pointer items-center gap-2.5 rounded-xl border-2 bg-white p-2.5 shadow-card transition
                                                      {{ $method === $methodOption->value ? 'border-stitch-primary ring-2 ring-stitch-primary/15' : 'border-stitch-border hover:border-stitch-high' }}">
                                            <input type="radio" value="{{ $methodOption->value }}" wire:model="method" class="sr-only" data-test="method" />

                                            @if ($methodOption->value === 'mtn_momo')
                                                <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-stitch-mtn text-[9px] font-black text-black">MTN</span>
                                            @else
                                                <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-stitch-orange text-[9px] font-black text-white">OM</span>
                                            @endif

                                            <span class="truncate text-xs font-bold">{{ $methodOption->label() }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <flux:input wire:model="phone" :label="__('Numéro de téléphone')" placeholder="+237 6XX XX XX XX" data-test="phone" />

                            <button type="submit"
                                    class="flex h-12 w-full items-center justify-center gap-2 rounded-full bg-stitch-terra text-base font-bold text-white shadow-raised transition-colors hover:bg-[#a85c1f] active:scale-[0.99]"
                                    data-test="buy">
                                <flux:icon.credit-card class="size-5" />
                                {{ __('Acheter :amount', ['amount' => $training->price->format()]) }}
                            </button>
                        </form>
                    </div>
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
                        {{ __('Votre compte ne permet pas d\'acheter cette formation.') }}
                    </flux:callout.text>
                </flux:callout>
            @endif

            {{-- Module list: titles are public, files stay behind the
                 entitlement-checked controller (business rule RG05). --}}
            <div class="flex flex-col gap-2">
                <flux:heading size="sm">{{ __('Contenu de la formation') }}</flux:heading>

                @forelse ($training->contents as $index => $content)
                    <div class="flex items-center justify-between gap-3 rounded-xl border border-stitch-border bg-white px-3.5 py-2.5 shadow-card">
                        <div class="flex min-w-0 items-center gap-2.5">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-full {{ $content->type->value === 'pdf' ? 'bg-stitch-terra-soft text-stitch-terra-ink' : 'bg-stitch-primary/10 text-stitch-primary' }}">
                                @if ($content->type->value === 'pdf')
                                    <flux:icon.document-text class="size-4" />
                                @else
                                    <flux:icon.video-camera class="size-4" />
                                @endif
                            </span>
                            <div class="min-w-0">
                                <flux:text class="truncate font-medium">{{ $content->title }}</flux:text>
                                <span class="text-xs text-stitch-muted">
                                    {{ __('Module :number', ['number' => $index + 1]) }}
                                </span>
                            </div>
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
        </div>
    </div>
</div>

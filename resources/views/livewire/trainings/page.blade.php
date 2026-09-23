{{--
    Reproduction de `agritech_fiche_formation` : retour, visuel de couverture
    avec ses pastilles, titre et description, carte du formateur, les deux
    options d'accès, le récapitulatif de ce que contient la formation, le
    programme des modules, et la barre d'achat épinglée.

    Écarts, toujours la même règle. La maquette affiche une note de 4,9 sur
    128 avis et « 98 % de réussite » : aucun avis n'est collecté. Elle promet
    un extrait gratuit, une bande-annonce, un certificat, un niveau, des
    prérequis et des témoignages : rien de tout cela n'est stocké. Ce qui
    reste — modules, format, prix, producteur, date de mise à jour — est lu
    en base.
--}}
@php
    $profile = $training->farmer->farmerProfile;
    $place = collect([$profile?->city, $profile?->region])->filter()->join(', ');
    $modules = $training->contents->count();
    $isPdf = $training->format === \App\Enums\TrainingFormat::Pdf;
@endphp

<div class="flex w-full flex-col gap-space-md pb-28 lg:pb-space-lg">
    <div class="flex items-center justify-between gap-space-sm">
        <a href="{{ route('trainings.index') }}" wire:navigate
           class="inline-flex items-center gap-1.5 text-primary font-label-lg text-label-lg py-1 hover:opacity-80 transition-opacity">
            <x-icon name="arrow_back" size="18" />
            <span>{{ __('Retour aux formations') }}</span>
        </a>
    </div>

    {{-- Couverture --}}
    <div class="relative w-full aspect-[16/10] rounded-xl overflow-hidden shadow-raised bg-surface-container-high">
        @if ($training->hasCover())
            <img src="{{ $training->coverUrl() }}" alt="{{ $training->title }}" class="w-full h-full object-cover" />
        @else
            <div class="flex h-full w-full items-center justify-center text-surface-container-highest">
                <x-icon name="school" size="64" />
            </div>
        @endif

        <div class="absolute inset-0 bg-gradient-to-t from-inverse-surface/80 via-inverse-surface/25 to-inverse-surface/35"></div>

        <div class="absolute top-3 left-3 right-3 flex flex-wrap gap-1.5 z-10">
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-inverse-surface/70 backdrop-blur-md text-on-primary font-label-sm text-label-sm">
                <x-icon :name="$isPdf ? 'description' : 'play_circle'" size="14" class="text-tertiary-fixed" />
                {{ $training->format->label() }}
            </span>

            @if ($modules > 0)
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-inverse-surface/70 backdrop-blur-md text-on-primary font-label-sm text-label-sm">
                    <x-icon name="layers" size="14" class="text-tertiary-fixed" />
                    {{ trans_choice(':count module|:count modules', $modules, ['count' => $modules]) }}
                </span>
            @endif

            @if ($training->included_in_subscription)
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-tertiary-fixed text-on-tertiary-fixed font-label-sm text-label-sm font-semibold ml-auto">
                    <x-icon name="star" size="14" filled />
                    {{ __('Incluse au Pass') }}
                </span>
            @endif
        </div>

        @if ($this->hasAccess())
            <a href="{{ route('trainings.read', ['training' => $training->slug]) }}" wire:navigate
               aria-label="{{ __('Ouvrir le lecteur') }}"
               class="absolute inset-0 m-auto w-14 h-14 rounded-full bg-surface/90 text-primary flex items-center justify-center shadow-raised backdrop-blur-sm hover:bg-surface transition-colors">
                <x-icon name="play_arrow" size="32" filled />
            </a>
        @endif
    </div>

    {{-- Titre --}}
    <section class="flex flex-col gap-space-xs">
        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-surface-container text-text-secondary font-label-sm text-label-sm">
                <x-icon name="calendar_today" size="14" />
                {{ __('Mis à jour : :date', [
                    'date' => $training->updated_at?->timezone(config('app.timezone'))->translatedFormat('F Y'),
                ]) }}
            </span>

            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-surface-container text-primary font-label-sm text-label-sm">
                <x-icon name="shield" size="14" />
                {{ __('Accès vérifié côté serveur') }}
            </span>
        </div>

        <h1 class="font-headline-lg-mobile text-headline-lg-mobile text-text-primary leading-tight mt-1">
            {{ $training->title }}
        </h1>

        <p class="font-body-md text-body-md text-text-secondary whitespace-pre-line leading-relaxed">
            {{ $training->description }}
        </p>
    </section>

    {{-- Formateur --}}
    <section class="p-space-md rounded-xl bg-surface-container-lowest shadow-card flex flex-col gap-3">
        <div class="flex items-center gap-3 min-w-0">
            <span class="w-12 h-12 rounded-full bg-surface-container-high flex items-center justify-center font-headline-sm text-primary shrink-0">
                {{ $training->farmer->initials() }}
            </span>

            <div class="flex flex-col min-w-0 flex-1">
                <div class="flex items-center gap-1.5 min-w-0">
                    <span class="font-headline-sm text-headline-sm text-text-primary truncate">
                        {{ $profile?->farm_name ?? $training->farmer->name }}
                    </span>
                    <x-icon name="verified" size="17" filled class="text-primary shrink-0" />
                </div>
                <p class="font-label-sm text-label-sm text-text-secondary line-clamp-1">
                    {{ $place !== '' ? $place : __('Producteur AgriTech') }}
                </p>
            </div>
        </div>

        @auth
            <a href="{{ route(auth()->user()->isFarmer() ? 'farmer.messages' : 'client.messages') }}" wire:navigate
               class="py-2 px-3 rounded-full bg-surface-container text-primary font-label-lg text-label-lg hover:bg-surface-container-high transition-colors flex items-center justify-center gap-1.5">
                <x-icon name="chat" size="18" />
                {{ __('Poser une question') }}
            </a>
        @endauth
    </section>

    {{-- Options d'accès --}}
    @if ($this->hasAccess())
        <section class="p-space-md rounded-xl bg-primary-fixed flex items-center justify-between gap-space-sm">
            <div class="flex items-center gap-space-sm min-w-0">
                <x-icon name="check_circle" size="24" filled class="text-primary shrink-0" />
                <div class="flex flex-col min-w-0">
                    <span class="font-label-lg text-label-lg text-on-primary-fixed">{{ __('Vous avez accès à cette formation.') }}</span>
                    <span class="font-label-sm text-label-sm text-on-primary-fixed-variant">{{ __('Reprenez où vous voulez, module par module.') }}</span>
                </div>
            </div>

            <a href="{{ route('trainings.read', ['training' => $training->slug]) }}" wire:navigate
               class="shrink-0 h-11 px-5 rounded-full bg-primary text-on-primary font-label-lg text-label-lg flex items-center gap-1.5 shadow-card hover:bg-primary-container transition-colors">
                <x-icon name="play_arrow" size="18" filled />
                <span>{{ __('Lire') }}</span>
            </a>
        </section>
    @else
        <section class="flex flex-col gap-3" id="acheter">
            @if ($training->included_in_subscription)
                <div class="p-space-md rounded-xl bg-tertiary-fixed/35 flex flex-col gap-3">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex flex-col min-w-0">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-tertiary-fixed text-on-tertiary-fixed font-label-sm text-[11px] font-bold w-fit mb-1">
                                <x-icon name="workspace_premium" size="13" />
                                {{ __('RECOMMANDÉ') }}
                            </span>
                            <span class="font-headline-sm text-headline-sm text-on-surface">{{ __('Option 1 — Pass Formations') }}</span>
                            <span class="font-label-sm text-label-sm text-on-surface-variant mt-0.5">
                                {{ __('Accès à toutes les formations incluses, pendant la durée du Pass.') }}
                            </span>
                        </div>

                        @if ($this->entryPlan())
                            <div class="text-right shrink-0">
                                <div class="font-price-tag text-price-tag text-primary leading-tight">{{ $this->entryPlan()->price->format() }}</div>
                                <span class="font-label-sm text-[11px] text-text-secondary">
                                    {{ trans_choice('/ :count jour|/ :count jours', $this->entryPlan()->duration_days, ['count' => $this->entryPlan()->duration_days]) }}
                                </span>
                            </div>
                        @endif
                    </div>

                    <a href="{{ route('client.subscriptions') }}" wire:navigate
                       class="w-full py-3 px-4 rounded-full bg-secondary text-on-secondary font-label-lg text-label-lg text-center shadow-card flex items-center justify-center gap-2 hover:opacity-95 transition-opacity">
                        <x-icon name="bolt" size="20" />
                        {{ __('Accéder avec l\'abonnement') }}
                    </a>
                </div>
            @endif

            <div class="p-space-md rounded-xl bg-surface-container-lowest shadow-card flex flex-col gap-space-sm">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex flex-col min-w-0">
                        <span class="font-label-lg text-label-lg text-text-primary">
                            {{ $training->included_in_subscription ? __('Option 2 — Achat à l\'unité') : __('Achat à l\'unité') }}
                        </span>
                        <span class="font-label-sm text-label-sm text-text-secondary">{{ __('Accès permanent, sans abonnement.') }}</span>
                    </div>

                    <div class="text-right shrink-0">
                        <div class="font-price-tag text-price-tag text-text-primary" data-test="training-price">{{ $training->price->format() }}</div>
                        <span class="font-label-sm text-[11px] text-status-success font-medium">{{ __('Paiement unique') }}</span>
                    </div>
                </div>

                @if ($this->canBuy())
                    <form wire:submit="buy" class="flex flex-col gap-space-sm pt-1">
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach (\App\Enums\PaymentMethod::cases() as $methodOption)
                                <label @class([
                                    'relative flex cursor-pointer items-center gap-2.5 rounded-xl p-2.5 transition-colors',
                                    'bg-[#FFF9E6] ring-2 ring-[#CA8A04]' => $method === $methodOption->value && $methodOption === \App\Enums\PaymentMethod::MtnMomo,
                                    'bg-[#FFF5ED] ring-2 ring-[#EA580C]' => $method === $methodOption->value && $methodOption === \App\Enums\PaymentMethod::OrangeMoney,
                                    'bg-surface-container hover:bg-surface-container-high' => $method !== $methodOption->value,
                                ])>
                                    <input type="radio" value="{{ $methodOption->value }}" wire:model.live="method" class="sr-only" data-test="method" />
                                    <span @class([
                                        'w-8 h-8 shrink-0 rounded-full flex items-center justify-center font-headline-sm text-[11px]',
                                        'bg-payment-mtn text-text-primary' => $methodOption === \App\Enums\PaymentMethod::MtnMomo,
                                        'bg-payment-orange text-on-primary' => $methodOption === \App\Enums\PaymentMethod::OrangeMoney,
                                    ])>{{ $methodOption === \App\Enums\PaymentMethod::MtnMomo ? 'M' : 'O' }}</span>
                                    <span class="truncate font-label-lg text-label-lg text-text-primary">{{ $methodOption->label() }}</span>
                                </label>
                            @endforeach
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="training-payer-phone" class="font-label-lg text-label-lg text-text-secondary">
                                {{ __('Numéro du compte payeur') }}
                            </label>

                            <div class="flex items-center bg-surface-container-low rounded-xl px-space-sm py-1.5 focus-within:bg-surface-white focus-within:shadow-raised transition-all">
                                <span class="font-headline-sm text-headline-sm tracking-tight pr-3 pl-1 text-on-surface select-none">+237</span>
                                <span class="h-6 w-0.5 bg-outline-variant mr-3"></span>
                                <input id="training-payer-phone" type="tel" wire:model="phone" data-test="phone"
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

                        <button type="submit" data-test="buy"
                                class="h-12 w-full rounded-full bg-primary text-on-primary font-label-lg text-label-lg flex items-center justify-center gap-2 shadow-card hover:bg-primary-container transition-colors">
                            <x-icon name="shopping_bag" size="18" />
                            {{ __('Acheter (:amount)', ['amount' => $training->price->format()]) }}
                        </button>

                        <p class="font-label-sm text-label-sm text-text-secondary flex items-start gap-1.5">
                            <x-icon name="shield" size="16" class="text-primary shrink-0 mt-0.5" />
                            {{ __('Paiement Mobile Money simulé. L\'accès s\'ouvre à la confirmation du serveur, jamais sur un simple retour du navigateur.') }}
                        </p>
                    </form>
                @elseif ($this->isVisitor())
                    <a href="{{ route('login') }}" wire:navigate
                       class="h-12 w-full rounded-full bg-primary text-on-primary font-label-lg text-label-lg flex items-center justify-center gap-2 shadow-card hover:bg-primary-container transition-colors">
                        <x-icon name="login" size="18" />
                        {{ __('Se connecter pour acheter') }}
                    </a>
                @else
                    <p class="font-label-sm text-label-sm text-text-secondary bg-surface-container rounded-lg p-space-sm">
                        {{ __('Votre compte ne permet pas d\'acheter cette formation.') }}
                    </p>
                @endif
            </div>
        </section>
    @endif

    {{-- Ce que comprend la formation --}}
    <section class="p-space-md rounded-xl bg-surface-container-low flex flex-col gap-2.5">
        <span class="font-label-lg text-label-lg text-text-primary uppercase tracking-wide">
            {{ __('Ce que comprend cette formation') }}
        </span>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-text-primary font-label-sm text-label-sm">
            @foreach ([
                trans_choice(':count module à lire dans l\'ordre|:count modules à lire dans l\'ordre', $modules, ['count' => $modules]),
                __('Format : :format', ['format' => $training->format->label()]),
                __('Accès permanent après achat'),
                $training->included_in_subscription
                    ? __('Comprise dans le Pass Formations')
                    : __('Vendue à l\'unité, hors Pass'),
            ] as $highlight)
                <div class="flex items-start gap-2">
                    <x-icon name="check_circle" size="18" class="text-primary shrink-0 mt-0.5" />
                    <span>{{ $highlight }}</span>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Programme --}}
    <section class="flex flex-col gap-space-sm">
        <div class="flex items-center justify-between gap-space-sm">
            <div class="flex items-center gap-2 min-w-0">
                <h2 class="font-headline-sm text-headline-sm text-text-primary">{{ __('Programme des modules') }}</h2>
                @if ($modules > 0)
                    <span class="px-2 py-0.5 rounded-full bg-surface-container text-text-secondary font-label-sm text-label-sm shrink-0">
                        {{ trans_choice(':count module|:count modules', $modules, ['count' => $modules]) }}
                    </span>
                @endif
            </div>
        </div>

        <div class="flex flex-col gap-2">
            @forelse ($training->contents as $index => $content)
                <div @class([
                    'p-3.5 rounded-xl flex items-start justify-between gap-3',
                    'bg-surface-container-lowest shadow-card' => $this->hasAccess(),
                    'bg-surface-container-low' => ! $this->hasAccess(),
                ])>
                    <div class="flex items-start gap-2.5 min-w-0">
                        <span @class([
                            'w-6 h-6 rounded-full font-label-sm text-label-sm font-bold flex items-center justify-center shrink-0 mt-0.5',
                            'bg-primary-fixed text-primary' => $this->hasAccess(),
                            'bg-surface-container-high text-text-secondary' => ! $this->hasAccess(),
                        ])>{{ $index + 1 }}</span>

                        <div class="flex flex-col min-w-0">
                            <span class="font-label-lg text-label-lg text-text-primary">{{ $content->title }}</span>
                            <div class="flex items-center gap-2 mt-0.5 text-text-secondary font-label-sm text-label-sm">
                                <span class="flex items-center gap-1">
                                    <x-icon :name="$content->type === \App\Enums\TrainingContentType::Video ? 'play_circle' : 'description'" size="14" />
                                    {{ $content->type->label() }}
                                </span>
                            </div>
                        </div>
                    </div>

                    @if ($this->hasAccess())
                        <a href="{{ route('trainings.read.module', ['training' => $training->slug, 'content' => $content->id]) }}"
                           wire:navigate
                           class="shrink-0 px-3.5 py-1.5 rounded-full bg-primary-fixed text-primary font-label-sm text-label-sm font-semibold flex items-center gap-1 hover:bg-primary-fixed-dim transition-colors">
                            <x-icon name="play_circle" size="16" />
                            {{ __('Ouvrir') }}
                        </a>
                    @else
                        <x-icon name="lock" size="18" class="text-outline shrink-0 mt-1" />
                    @endif
                </div>
            @empty
                <p class="font-body-md text-body-md text-text-secondary bg-surface-container-low rounded-xl p-space-md">
                    {{ __('Le contenu sera publié prochainement.') }}
                </p>
            @endforelse
        </div>
    </section>

    {{-- Barre d'achat épinglée --}}
    @if (! $this->hasAccess())
        <div class="fixed bottom-16 lg:bottom-0 inset-x-0 lg:left-64 z-40 px-margin py-2 bg-surface/95 backdrop-blur-xl shadow-[0_-4px_16px_rgba(31,36,33,0.08)]">
            <div class="max-w-md lg:max-w-3xl mx-auto flex items-center justify-between gap-3">
                <div class="flex flex-col min-w-0 shrink-0">
                    <span class="font-label-sm text-label-sm text-text-secondary">{{ __('Prix d\'accès') }}</span>
                    <span class="font-price-tag text-price-tag text-primary leading-tight">{{ $training->price->format() }}</span>
                    @if ($training->included_in_subscription && $this->entryPlan())
                        <a href="{{ route('client.subscriptions') }}" wire:navigate
                           class="font-label-sm text-[11px] text-secondary font-semibold hover:underline">
                            {{ __('ou :amount avec le Pass', ['amount' => $this->entryPlan()->price->format()]) }}
                        </a>
                    @endif
                </div>

                <a href="#acheter"
                   class="flex-1 max-w-[220px] h-12 rounded-full bg-primary text-on-primary font-label-lg text-label-lg flex items-center justify-center gap-1.5 shadow-raised hover:bg-primary-container transition-colors">
                    <x-icon name="shopping_bag" size="18" />
                    <span class="truncate">{{ __('Acheter') }}</span>
                </a>
            </div>
        </div>
    @endif
</div>

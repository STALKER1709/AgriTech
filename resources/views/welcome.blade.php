<x-layouts::public :title="__('Bienvenue')">
    {{--
        Accueil. Le dossier de maquettes n'en contient pas : l'onglet
        « Accueil » de la barre basse n'a pas d'écran associé. La page est donc
        écrite dans le vocabulaire exact des autres écrans — ligne de contexte
        en terre cuite, pastille « Réseau en direct », titre
        `headline-lg-mobile`, cartes produit identiques à celles du catalogue —
        plutôt que dessinée à part.
    --}}
    <div class="flex flex-col gap-space-lg py-space-sm">
        {{-- Bandeau d'accueil. Photographie libre enregistrée dans le dépôt
             (voir /credits-photos) : rien n'est chargé depuis un service
             distant, l'application reste utilisable hors ligne. --}}
        <section class="relative overflow-hidden rounded-2xl shadow-raised">
            <img src="{{ asset('images/accueil-hero.jpg') }}" alt=""
                 class="absolute inset-0 h-full w-full object-cover" />

            <div class="absolute inset-0 bg-gradient-to-t from-inverse-surface/85 via-inverse-surface/55 to-inverse-surface/15"></div>

            <div class="relative flex flex-col gap-1 p-space-md lg:p-space-lg min-h-[13rem] lg:min-h-[19rem] justify-end">
                <div class="flex items-center justify-between gap-space-sm">
                    <span class="font-label-sm text-label-sm text-tertiary-fixed uppercase tracking-wider font-semibold">
                        {{ __('Cameroun • Terroirs Unis') }}
                    </span>
                    <span class="inline-flex items-center gap-1 font-label-sm text-label-sm text-inverse-on-surface bg-inverse-surface/50 px-2 py-0.5 rounded-full shrink-0 backdrop-blur-sm">
                        <span class="w-1.5 h-1.5 rounded-full bg-status-success"></span>
                        {{ __('Réseau en direct') }}
                    </span>
                </div>

                <h1 class="font-headline-lg-mobile text-headline-lg-mobile lg:font-display-lg lg:text-display-lg text-inverse-on-surface tracking-tight">
                    {{ __('Le marché fermier du Cameroun') }}
                </h1>

                <p class="font-body-md text-body-md text-inverse-on-surface/90 leading-snug max-w-xl">
                    {{ __('Des produits frais en direct des producteurs, des formations pratiques, et le paiement Mobile Money.') }}
                </p>
            </div>
        </section>

        {{-- Actions principales --}}
        <section class="flex flex-wrap gap-space-sm">
            <x-button :href="route('catalog.browse')" icon="storefront">
                {{ __('Parcourir le catalogue') }}
            </x-button>

            <x-button variant="outline" :href="route('trainings.index')" icon="school">
                {{ __('Voir les formations') }}
            </x-button>
        </section>

        {{-- Ce que fait la plateforme --}}
        <section class="grid gap-space-sm sm:grid-cols-3">
            @foreach ([
                ['storefront', 'bg-primary/10 text-primary', __('Acheter en direct'), __('Des produits vendus par ceux qui les cultivent, sans intermédiaire.')],
                ['school', 'bg-secondary-fixed text-on-secondary-fixed', __('Se former'), __('Des formations vidéo et PDF proposées par des agriculteurs expérimentés.')],
                ['smartphone', 'bg-tertiary-fixed text-on-tertiary-fixed', __('Payer simplement'), __('MTN Mobile Money et Orange Money, depuis votre téléphone.')],
            ] as [$icon, $tint, $title, $text])
                <x-card class="flex flex-col gap-space-sm">
                    <span class="w-11 h-11 rounded-full flex items-center justify-center {{ $tint }}">
                        <x-icon :name="$icon" size="22" />
                    </span>
                    <h2 class="font-headline-sm text-headline-sm text-text-primary">{{ $title }}</h2>
                    <p class="font-body-md text-body-md text-text-secondary leading-snug">{{ $text }}</p>
                </x-card>
            @endforeach
        </section>

        {{-- Aperçu du catalogue, lu dans la même requête que le catalogue --}}
        @php($latest = \App\Models\Product::query()
            ->visibleToPublic()
            ->with(['images', 'category', 'farmer.farmerProfile'])
            ->latest()
            ->limit(4)
            ->get())

        @if ($latest->isNotEmpty())
            <section class="flex flex-col gap-space-sm">
                <div class="flex items-end justify-between gap-space-sm">
                    <div class="min-w-0">
                        <h2 class="font-headline-md text-headline-md text-text-primary tracking-tight">
                            {{ __('Fraîchement récolté') }}
                        </h2>
                        <p class="font-label-sm text-label-sm text-text-secondary">
                            {{ __('Un aperçu des produits du moment.') }}
                        </p>
                    </div>

                    <a href="{{ route('catalog.browse') }}" wire:navigate
                       class="inline-flex items-center gap-1 font-label-lg text-label-lg text-primary shrink-0 hover:underline">
                        {{ __('Tout voir') }}
                        <x-icon name="chevron_right" size="18" />
                    </a>
                </div>

                <div class="grid grid-cols-2 gap-gutter lg:grid-cols-4">
                    @foreach ($latest as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Formations à l'affiche --}}
        @php($trainings = \App\Models\Training::query()
            ->visibleToPublic()
            ->with('farmer.farmerProfile')
            ->withCount('contents')
            ->latest()
            ->limit(2)
            ->get())

        @if ($trainings->isNotEmpty())
            <section class="flex flex-col gap-space-sm">
                <div class="flex items-end justify-between gap-space-sm">
                    <h2 class="font-headline-md text-headline-md text-text-primary tracking-tight">
                        {{ __('Se former avec eux') }}
                    </h2>

                    <a href="{{ route('trainings.index') }}" wire:navigate
                       class="inline-flex items-center gap-1 font-label-lg text-label-lg text-primary shrink-0 hover:underline">
                        {{ __('Tout voir') }}
                        <x-icon name="chevron_right" size="18" />
                    </a>
                </div>

                <div class="grid gap-gutter sm:grid-cols-2">
                    @foreach ($trainings as $training)
                        <x-training-card :training="$training" />
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Recrutement des producteurs --}}
        @guest
            <section class="rounded-xl bg-primary text-on-primary p-space-md lg:p-space-lg shadow-raised">
                <div class="flex flex-col gap-space-sm lg:flex-row lg:items-center lg:justify-between">
                    <div class="max-w-xl">
                        <span class="inline-flex h-6 items-center gap-1 rounded-full bg-tertiary-fixed px-2.5 font-label-sm text-label-sm text-on-tertiary-fixed">
                            {{ __('Espace producteur') }}
                        </span>

                        <h2 class="font-headline-md text-headline-md mt-space-xs">
                            {{ __('Vous êtes agriculteur ? Vendez votre récolte en direct.') }}
                        </h2>

                        <p class="font-body-md text-body-md text-on-primary/85 mt-1 leading-snug">
                            {{ __('Créez votre fiche d\'exploitation, publiez vos produits et vos formations, encaissez par Mobile Money.') }}
                        </p>
                    </div>

                    <x-button :href="route('register.farmer')"
                              class="shrink-0 !bg-surface-container-lowest !text-primary hover:!bg-surface-container-low">
                        {{ __('Créer mon compte agriculteur') }}
                    </x-button>
                </div>
            </section>
        @endguest
    </div>
</x-layouts::public>

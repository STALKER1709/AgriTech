<x-layouts::public :title="__('Bienvenue')">
    {{-- Landing page following the Stitch catalogue/home screen: greeting
         block, pill CTAs, product grid preview and training shelf. --}}
    <div class="flex flex-col gap-10 py-6">
        {{-- Hero --}}
        <section class="flex flex-col gap-5">
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-stitch-primary/10 px-3 py-1 text-xs font-semibold tracking-wide text-stitch-primary uppercase">
                    <flux:icon.map-pin class="size-3.5" />
                    {{ __('Cameroun • Terroirs Unis') }}
                </span>
                <span class="stitch-badge-success">
                    <span class="size-1.5 rounded-full bg-stitch-success"></span>
                    {{ __('Circuits courts en direct') }}
                </span>
            </div>

            <h1 class="max-w-2xl font-display text-3xl font-bold tracking-tight text-stitch-ink sm:text-4xl sm:leading-[1.15]">
                {{ __('Des produits frais en direct des producteurs locaux') }}
            </h1>

            <p class="max-w-xl text-base leading-relaxed text-stitch-muted">
                {{ __('AgriTech met en relation les agriculteurs et leurs clients : produits frais, formations pratiques, et paiement Mobile Money. Le marché fermier du Cameroun, dans votre poche.') }}
            </p>

            <div class="flex flex-wrap gap-3">
                <flux:button variant="primary" :href="route('catalog.browse')" wire:navigate class="rounded-full">
                    {{ __('Parcourir le catalogue') }}
                </flux:button>

                <flux:button variant="outline" :href="route('trainings.index')" wire:navigate class="rounded-full">
                    {{ __('Voir les formations') }}
                </flux:button>

                @guest
                    <flux:button variant="ghost" :href="route('register.farmer')" wire:navigate class="rounded-full">
                        {{ __('Vendre mes produits') }}
                    </flux:button>
                @endguest
            </div>
        </section>

        {{-- Value cards --}}
        <section class="grid gap-4 sm:grid-cols-3">
            <div class="stitch-card flex flex-col gap-3 p-5">
                <span class="flex size-11 items-center justify-center rounded-full bg-stitch-primary/10 text-stitch-primary">
                    <flux:icon.shopping-bag class="size-5" />
                </span>
                <flux:heading size="sm">{{ __('Acheter en direct') }}</flux:heading>
                <flux:text class="text-sm">
                    {{ __('Des produits vendus par ceux qui les cultivent, sans intermédiaire ni commission cachée.') }}
                </flux:text>
            </div>

            <div class="stitch-card flex flex-col gap-3 p-5">
                <span class="flex size-11 items-center justify-center rounded-full bg-stitch-terra-soft text-stitch-terra-ink">
                    <flux:icon.academic-cap class="size-5" />
                </span>
                <flux:heading size="sm">{{ __('Se former') }}</flux:heading>
                <flux:text class="text-sm">
                    {{ __('Des formations vidéo et PDF proposées par des agriculteurs expérimentés.') }}
                </flux:text>
            </div>

            <div class="stitch-card flex flex-col gap-3 p-5">
                <span class="flex size-11 items-center justify-center rounded-full bg-stitch-gold-soft text-stitch-gold-ink">
                    <flux:icon.credit-card class="size-5" />
                </span>
                <flux:heading size="sm">{{ __('Payer simplement') }}</flux:heading>
                <flux:text class="text-sm">
                    {{ __('MTN Mobile Money et Orange Money, depuis votre téléphone, sans frais cachés.') }}
                </flux:text>
            </div>
        </section>

        {{-- Latest produce, straight from the catalogue query. --}}
        <section class="flex flex-col gap-4">
            <div class="flex items-end justify-between gap-3">
                <div>
                    <flux:heading size="md">{{ __('Fraîchement récolté') }}</flux:heading>
                    <flux:text class="text-sm">{{ __('Un aperçu des produits du moment.') }}</flux:text>
                </div>
                <a href="{{ route('catalog.browse') }}" wire:navigate
                   class="inline-flex items-center gap-1 rounded-full bg-white px-4 py-2 text-sm font-semibold text-stitch-primary shadow-card hover:shadow-raised">
                    {{ __('Tout voir') }}
                    <flux:icon.chevron-right class="size-4" />
                </a>
            </div>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                @foreach (\App\Models\Product::query()
                    ->visibleToPublic()
                    ->with(['images', 'farmer.farmerProfile', 'category'])
                    ->latest()
                    ->limit(8)
                    ->get() as $product)
                    <a href="{{ route('catalog.product', ['product' => $product->slug]) }}" wire:navigate
                       class="stitch-card group flex flex-col overflow-hidden p-2.5 transition-shadow hover:shadow-raised">
                        <div class="relative aspect-square w-full overflow-hidden rounded-lg bg-stitch-container">
                            @if ($product->images->isNotEmpty())
                                <img src="{{ $product->images->first()->url() }}" alt="{{ $product->name }}"
                                     loading="lazy"
                                     class="size-full object-cover transition-transform duration-300 group-hover:scale-105" />
                            @else
                                <div class="flex size-full items-center justify-center">
                                    <flux:icon.photo class="size-8 text-stitch-highest" />
                                </div>
                            @endif
                        </div>

                        <div class="mt-2 flex min-w-0 flex-col px-0.5">
                            <span class="text-xs text-stitch-muted">
                                {{ $product->farmer->farmerProfile?->region }}
                            </span>
                            <flux:heading size="sm" class="mt-0.5 line-clamp-2 leading-tight">
                                {{ $product->name }}
                            </flux:heading>
                        </div>

                        <div class="mt-2 flex items-end justify-between px-0.5 pb-0.5 pt-1">
                            <div class="flex min-w-0 flex-col">
                                <span class="stitch-price text-base leading-tight">
                                    {{ $product->unit_price->format() }}
                                </span>
                                <span class="text-[11px] text-stitch-muted">
                                    / {{ $product->unit->shortLabel() }}
                                </span>
                            </div>
                            <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-stitch-primary text-white shadow-card transition-transform group-hover:scale-105">
                                <flux:icon.plus class="size-4" />
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>

        {{-- Farmer recruitment banner --}}
        @guest
            <section class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-stitch-primary to-[#0d3f20] p-6 text-white shadow-raised sm:p-8">
                <span class="absolute -right-10 -bottom-10 size-40 rounded-full bg-stitch-gold-soft/15 blur-2xl" aria-hidden="true"></span>
                <div class="relative z-10 flex flex-col items-start gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="max-w-xl">
                        <span class="stitch-badge-gold mb-2">{{ __('Espace producteur') }}</span>
                        <h2 class="font-display text-xl font-bold sm:text-2xl">
                            {{ __('Vous êtes agriculteur ? Vendez votre récolte en direct.') }}
                        </h2>
                        <p class="mt-2 text-sm leading-relaxed text-white/85">
                            {{ __('Créez votre fiche d\'exploitation, publiez vos produits et vos formations, encaissez par Mobile Money.') }}
                        </p>
                    </div>
                    <flux:button class="shrink-0 rounded-full bg-white text-stitch-primary hover:bg-white/90"
                                 :href="route('register.farmer')" wire:navigate>
                        {{ __('Créer mon compte agriculteur') }}
                    </flux:button>
                </div>
            </section>
        @endguest
    </div>
</x-layouts::public>

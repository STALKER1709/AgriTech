<x-layouts::public :title="__('Bienvenue')">
    <div class="flex flex-col gap-12 py-10">
        <section class="flex flex-col items-start gap-5 sm:max-w-2xl">
            <flux:badge>{{ __('Cameroun') }}</flux:badge>

            <flux:heading size="xl" level="1" class="text-3xl sm:text-4xl">
                {{ __('Les produits de nos agriculteurs, en direct.') }}
            </flux:heading>

            <flux:text class="text-base">
                {{ __('AgriTech met en relation les agriculteurs et leurs clients : produits frais, formations, et paiement Mobile Money.') }}
            </flux:text>

            <div class="flex flex-wrap gap-3">
                <flux:button variant="primary" :href="route('catalog.browse')" wire:navigate>
                    {{ __('Parcourir le catalogue') }}
                </flux:button>

                <flux:button variant="outline" :href="route('trainings.index')" wire:navigate>
                    {{ __('Voir les formations') }}
                </flux:button>

                @guest
                    <flux:button variant="ghost" :href="route('register.farmer')" wire:navigate>
                        {{ __('Vendre mes produits') }}
                    </flux:button>
                @endguest
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                <flux:icon.shopping-bag class="size-6" />
                <flux:heading size="sm" class="mt-3">{{ __('Acheter en direct') }}</flux:heading>
                <flux:text class="mt-1 text-sm">
                    {{ __('Des produits vendus par ceux qui les cultivent, sans intermédiaire.') }}
                </flux:text>
            </div>

            <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                <flux:icon.academic-cap class="size-6" />
                <flux:heading size="sm" class="mt-3">{{ __('Se former') }}</flux:heading>
                <flux:text class="mt-1 text-sm">
                    {{ __('Des formations pratiques proposées par des agriculteurs expérimentés.') }}
                </flux:text>
            </div>

            <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                <flux:icon.device-phone-mobile class="size-6" />
                <flux:heading size="sm" class="mt-3">{{ __('Payer simplement') }}</flux:heading>
                <flux:text class="mt-1 text-sm">
                    {{ __('MTN Mobile Money et Orange Money, depuis votre téléphone.') }}
                </flux:text>
            </div>
        </section>
    </div>
</x-layouts::public>

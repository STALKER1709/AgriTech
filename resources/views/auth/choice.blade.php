{{--
    Reproduction de `agritech_choix_d_inscription` : pastille d'étape, motif
    de marque, deux cartes de rôle avec leur liste d'avantages, puis l'appel à
    l'action.

    La maquette promet une « validation simplifiée en 24 h » et un badge de
    certification : aucun délai n'est garanti et aucune certification n'est
    délivrée. Les avantages listés sont ceux que la plateforme rend vraiment.
--}}
<x-layouts::auth :title="__('Créer un compte')">
    <div class="flex flex-col gap-space-md" x-data="{ role: 'client' }">
        <div class="flex flex-col items-center text-center gap-space-xs">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-primary-fixed text-primary font-label-sm text-label-sm">
                <x-icon name="flag" size="14" />
                {{ __('Étape 1 sur 2 • Rejoindre AgriTech') }}
            </span>

            <span class="flex w-16 h-16 items-center justify-center rounded-full bg-primary text-on-primary shadow-raised mt-1">
                <x-icon name="eco" size="32" filled />
            </span>

            <h1 class="font-headline-lg-mobile text-headline-lg-mobile text-text-primary tracking-tight">
                {{ __('Vous êtes ?') }}
            </h1>
            <p class="font-body-md text-body-md text-text-secondary max-w-sm">
                {{ __('Ce choix décide de ce que vous pourrez faire. Il ne se change pas tout seul ensuite.') }}
            </p>
        </div>

        {{-- Cartes de rôle --}}
        @foreach ([
            [
                'key' => 'client',
                'icon' => 'shopping_basket',
                'title' => __('Client'),
                'lead' => __('Acheter des produits, suivre ses commandes, se former.'),
                'perks' => [
                    ['travel_explore', __('Parcourir le catalogue de tous les producteurs')],
                    ['play_circle', __('Acheter des formations, ou les ouvrir avec le Pass')],
                    ['payments', __('Payer par Mobile Money, MTN ou Orange')],
                ],
                'free' => __('Gratuit, immédiat'),
            ],
            [
                'key' => 'farmer',
                'icon' => 'agriculture',
                'title' => __('Agriculteur'),
                'lead' => __('Vendre ses récoltes et ses formations depuis son exploitation.'),
                'perks' => [
                    ['price_check', __('Fixer ses prix et publier son catalogue')],
                    ['school', __('Vendre des formations à ses pairs')],
                    ['account_balance_wallet', __('Suivre ses commandes payées et sa commission')],
                ],
                'free' => __('Frais d\'inscription à régler, puis validation par un administrateur'),
            ],
        ] as $card)
            <button type="button" x-on:click="role = '{{ $card['key'] }}'"
                    :class="role === '{{ $card['key'] }}' ? 'ring-2 ring-primary shadow-raised' : 'shadow-card hover:shadow-raised'"
                    class="relative overflow-hidden text-left rounded-2xl bg-surface-container-lowest p-space-md flex flex-col gap-space-sm transition-shadow"
                    data-test="role-{{ $card['key'] }}">
                <span class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-secondary to-tertiary-fixed-dim"
                      x-show="role === '{{ $card['key'] }}'" x-cloak></span>

                <div class="flex items-start justify-between gap-space-sm pt-1">
                    <div class="flex items-center gap-space-sm min-w-0">
                        <span class="w-12 h-12 rounded-full bg-primary-fixed text-primary flex items-center justify-center shrink-0">
                            <x-icon :name="$card['icon']" size="24" />
                        </span>
                        <div class="min-w-0">
                            <span class="font-headline-sm text-headline-sm text-text-primary block">{{ $card['title'] }}</span>
                            <span class="font-label-sm text-label-sm text-text-secondary">{{ $card['lead'] }}</span>
                        </div>
                    </div>

                    <span :class="role === '{{ $card['key'] }}' ? 'bg-primary text-on-primary' : 'bg-surface-container text-transparent'"
                          class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 transition-colors">
                        <x-icon name="check" size="16" />
                    </span>
                </div>

                <ul class="flex flex-col gap-1.5">
                    @foreach ($card['perks'] as [$icon, $perk])
                        <li class="flex items-start gap-2 font-label-sm text-label-sm text-text-primary">
                            <x-icon :name="$icon" size="18" class="text-primary shrink-0 mt-0.5" />
                            <span>{{ $perk }}</span>
                        </li>
                    @endforeach
                </ul>

                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-surface-container text-text-secondary font-label-sm text-label-sm w-fit">
                    <x-icon name="info" size="14" />
                    {{ $card['free'] }}
                </span>
            </button>
        @endforeach

        {{-- Moyens de paiement, annoncés tels qu'ils sont : simulés. --}}
        <div class="rounded-xl bg-surface-container-low p-space-sm flex flex-wrap items-center gap-2">
            <span class="font-label-sm text-label-sm text-text-secondary">{{ __('Paiements pris en charge :') }}</span>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-surface-container-lowest font-label-sm text-label-sm text-text-primary shadow-card">
                <span class="w-3.5 h-3.5 rounded-full bg-payment-mtn inline-block"></span>
                MTN MoMo
            </span>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-surface-container-lowest font-label-sm text-label-sm text-text-primary shadow-card">
                <span class="w-3.5 h-3.5 rounded-full bg-payment-orange inline-block"></span>
                Orange Money
            </span>
            <span class="font-label-sm text-label-sm text-text-secondary">{{ __('(simulés localement)') }}</span>
        </div>

        <a href="{{ route('register') }}" wire:navigate x-show="role === 'client'"
           class="h-14 w-full rounded-full bg-primary text-on-primary font-label-lg text-label-lg flex items-center justify-center gap-2 shadow-raised hover:bg-primary-container transition-colors"
           data-test="continue-client">
            {{ __('Continuer comme client') }}
            <x-icon name="arrow_forward" size="20" />
        </a>

        <a href="{{ route('register.farmer') }}" wire:navigate x-show="role === 'farmer'" x-cloak
           class="h-14 w-full rounded-full bg-primary text-on-primary font-label-lg text-label-lg flex items-center justify-center gap-2 shadow-raised hover:bg-primary-container transition-colors"
           data-test="continue-farmer">
            {{ __('Continuer comme agriculteur') }}
            <x-icon name="arrow_forward" size="20" />
        </a>

        <p class="text-center font-body-md text-body-md text-text-secondary">
            {{ __('Vous avez déjà un compte ?') }}
            <a href="{{ route('login') }}" wire:navigate class="text-primary font-semibold hover:underline">
                {{ __('Se connecter') }}
            </a>
        </p>
    </div>
</x-layouts::auth>

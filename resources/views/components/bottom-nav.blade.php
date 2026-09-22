{{--
    Barre d'onglets basse des maquettes `mobile_tab` : hauteur 64 px, surface
    ivoire translucide, ombre portée vers le haut, cinq destinations à portée
    de pouce. Le marquage reprend celui de `agritech_catalogue_produits`.
--}}
<nav class="fixed bottom-0 inset-x-0 z-50 bg-surface/95 backdrop-blur-xl shadow-[0_-2px_12px_rgba(31,36,33,0.05)] lg:hidden"
     style="padding-bottom: env(safe-area-inset-bottom, 0px);"
     aria-label="{{ __('Navigation principale') }}">
    <div class="flex items-center justify-around h-16 px-space-xs">
        @foreach ($tabs as $tab)
            <a href="{{ $tab['href'] }}" wire:navigate
               @class([
                   'flex flex-col items-center justify-center min-w-[56px] h-12 gap-0.5 transition-colors',
                   'text-primary' => $tab['current'],
                   'text-text-secondary hover:text-primary' => ! $tab['current'],
               ])
               @if ($tab['current']) aria-current="page" @endif>
                <div class="relative">
                    <x-icon :name="$tab['icon']" size="24" :filled="$tab['current']" />

                    @if ($tab['badge'])
                        <span class="absolute -top-1 -right-1.5 min-w-[18px] h-[18px] px-1 bg-secondary text-on-secondary font-label-sm text-[10px] leading-tight font-bold rounded-full flex items-center justify-center ring-2 ring-surface">
                            {{ $tab['badge'] > 9 ? '9+' : $tab['badge'] }}
                        </span>
                    @endif
                </div>

                <span @class(['font-label-sm text-label-sm', 'font-body-md-bold' => $tab['current']])>
                    {{ $tab['label'] }}
                </span>
            </a>
        @endforeach
    </div>
</nav>

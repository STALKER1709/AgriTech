{{--
    Reproduction de `agritech_admin_param_tres` : chapeau, puis une carte par
    paramètre, avec son libellé, son explication et son contrôle.

    Écarts : la maquette range les réglages en onglets (général, paiements,
    notifications, sécurité) et propose des fenêtres de maintenance. Les
    paramètres tiennent dans une table de cinq lignes ; les répartir en
    quatre onglets ferait chercher plus longtemps qu'il n'en faut pour tout
    lire. Aucune maintenance n'est programmable.
--}}
<div class="flex w-full max-w-3xl flex-1 flex-col gap-space-md">
    <x-admin-header icon="settings"
                    :eyebrow="__('Règle RG11 — chaque changement est tracé')"
                    :title="__('Paramètres de la plateforme')"
                    :subtitle="__('Ces valeurs s\'appliquent immédiatement ; les montants déjà figés sur une commande ne changent pas.')" />

    <form wire:submit="save" class="flex flex-col gap-space-sm">
        @foreach ($settings as $setting)
            <div class="rounded-2xl bg-surface-container-lowest p-space-md shadow-card" data-test="setting-row">
                @if ($setting->type === \App\Enums\SettingType::Boolean)
                    <label class="flex items-start gap-space-sm cursor-pointer">
                        <input type="checkbox" wire:model="values.{{ $setting->key }}"
                               class="w-5 h-5 mt-0.5 rounded-md border-outline-variant text-primary focus:ring-primary shrink-0" />
                        <span class="min-w-0">
                            <span class="font-body-md-bold text-body-md-bold text-text-primary block">{{ $setting->label }}</span>
                            @if ($setting->description)
                                <span class="font-label-sm text-label-sm text-text-secondary">{{ $setting->description }}</span>
                            @endif
                            <span class="font-label-sm text-label-sm text-outline block mt-0.5">{{ $setting->key }}</span>
                        </span>
                    </label>
                @else
                    <x-form-field :wire="'values.'.$setting->key"
                                  :label="$setting->label"
                                  :icon="$setting->type === \App\Enums\SettingType::Integer ? 'pin' : 'tune'"
                                  :inputmode="$setting->type === \App\Enums\SettingType::Integer ? 'numeric' : null"
                                  :mark-optional="false"
                                  :hint="$setting->description" />

                    <span class="font-label-sm text-label-sm text-outline block mt-1.5">{{ $setting->key }}</span>
                @endif
            </div>
        @endforeach

        <button type="submit" data-test="save-settings" wire:loading.attr="disabled"
                class="h-14 rounded-full bg-primary text-on-primary font-label-lg text-label-lg flex items-center justify-center gap-2 shadow-raised hover:bg-primary-container transition-colors disabled:opacity-60">
            <x-icon name="save" size="20" />
            <span wire:loading.remove wire:target="save">{{ __('Enregistrer') }}</span>
            <span wire:loading wire:target="save">{{ __('Enregistrement…') }}</span>
        </button>
    </form>
</div>

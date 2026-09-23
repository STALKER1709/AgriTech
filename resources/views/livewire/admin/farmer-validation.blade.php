{{--
    Reproduction de `agritech_admin_agriculteurs_valider` : chapeau, puis un
    dossier par carte — identité, exploitation, coordonnées — avec ses deux
    décisions et le champ de motif qui s'ouvre au refus.

    Écarts : la maquette affiche des pièces jointes (CNI, registre GIC, titre
    foncier), un score de complétude et une carte de la parcelle. Un profil
    d'exploitation ne porte aucun document, et rien ne géolocalise. Ce qui est
    montré est ce que l'agriculteur a réellement déclaré.
--}}
<div class="flex w-full flex-1 flex-col gap-space-md">
    <x-admin-header icon="how_to_reg"
                    :eyebrow="__('Règle RG07 — décision tracée')"
                    :title="__('Agriculteurs à valider')"
                    :subtitle="__('Ces comptes ont réglé leurs frais d\'inscription et attendent une décision.')" />

    <div class="flex flex-col gap-space-md">
        @forelse ($farmers as $farmer)
            @php($profile = $farmer->farmerProfile)

            <article class="rounded-2xl bg-surface-container-lowest shadow-card overflow-hidden" data-test="farmer-row">
                <div class="p-space-md flex flex-col gap-space-sm">
                    <div class="flex items-start justify-between gap-space-sm">
                        <div class="flex items-center gap-space-sm min-w-0">
                            <span class="w-12 h-12 rounded-full bg-primary-fixed flex items-center justify-center font-headline-sm text-primary shrink-0">
                                {{ $farmer->initials() }}
                            </span>

                            <div class="min-w-0">
                                <h2 class="font-headline-sm text-headline-sm text-text-primary truncate">
                                    {{ $profile?->farm_name ?? __('Exploitation non renseignée') }}
                                </h2>
                                <span class="font-label-sm text-label-sm text-text-secondary truncate block">{{ $farmer->name }}</span>
                            </div>
                        </div>

                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-[#fff3e0] text-status-warning font-label-sm text-label-sm shrink-0">
                            <x-icon name="hourglass_top" size="14" />
                            {{ $farmer->status->label() }}
                        </span>
                    </div>

                    <dl class="grid gap-x-space-md gap-y-1.5 sm:grid-cols-2">
                        @foreach ([
                            ['location_on', __('Région'), $profile?->region],
                            ['pin_drop', __('Ville'), $profile?->city],
                            ['mail', __('E-mail'), $farmer->email],
                            ['phone_iphone', __('Téléphone'), $farmer->phone],
                            ['calendar_today', __('Inscrit le'), $farmer->created_at?->timezone(config('app.timezone'))->translatedFormat('d F Y')],
                        ] as [$icon, $term, $value])
                            <div class="flex items-center gap-2 min-w-0">
                                <x-icon :name="$icon" size="16" class="text-text-secondary shrink-0" />
                                <dt class="font-label-sm text-label-sm text-text-secondary shrink-0">{{ $term }}</dt>
                                <dd class="font-label-lg text-label-lg text-text-primary truncate">{{ $value ?: '—' }}</dd>
                            </div>
                        @endforeach
                    </dl>

                    @if ($profile?->description)
                        <p class="font-body-md text-body-md text-text-secondary leading-relaxed rounded-xl bg-surface-container-low p-space-sm">
                            {{ $profile->description }}
                        </p>
                    @endif
                </div>

                @if ($rejecting === $farmer->id)
                    <form wire:submit="reject" class="mx-space-md mb-space-md flex flex-col gap-space-sm rounded-xl bg-[#ffebee] p-space-md">
                        <x-form-field wire="reason" type="textarea" :label="__('Motif du refus')" icon="report" required
                                      :rows="3"
                                      :placeholder="__('Ce que l\'agriculteur doit corriger pour revenir.')"
                                      :hint="__('Ce motif lui est envoyé et s\'affiche sur son écran de statut.')" />

                        <div class="flex flex-wrap gap-space-sm">
                            <button type="submit" data-test="confirm-rejection"
                                    class="h-12 px-5 rounded-full bg-status-error text-on-error font-label-lg text-label-lg inline-flex items-center gap-1.5 shadow-card hover:opacity-90 transition-opacity">
                                <x-icon name="gpp_bad" size="18" />
                                {{ __('Confirmer le refus') }}
                            </button>

                            <button type="button" wire:click="cancelRejection"
                                    class="h-12 px-5 rounded-full bg-surface-container text-text-primary font-label-lg text-label-lg inline-flex items-center gap-1.5 hover:bg-surface-container-high transition-colors">
                                {{ __('Annuler') }}
                            </button>
                        </div>
                    </form>
                @else
                    <div class="px-space-md py-space-sm bg-surface-container-low flex flex-wrap gap-space-sm">
                        <button type="button" wire:click="approve({{ $farmer->id }})" data-test="approve-farmer"
                                class="h-11 px-5 rounded-full bg-primary text-on-primary font-label-lg text-label-lg inline-flex items-center gap-1.5 shadow-card hover:bg-primary-container transition-colors">
                            <x-icon name="verified" size="18" />
                            {{ __('Approuver') }}
                        </button>

                        <button type="button" wire:click="startRejection({{ $farmer->id }})" data-test="start-rejection"
                                class="h-11 px-5 rounded-full bg-surface-container text-status-error font-label-lg text-label-lg inline-flex items-center gap-1.5 hover:bg-surface-container-high transition-colors">
                            <x-icon name="block" size="18" />
                            {{ __('Refuser') }}
                        </button>
                    </div>
                @endif
            </article>
        @empty
            <div class="rounded-2xl bg-surface-container-lowest flex flex-col items-center gap-space-sm px-space-md py-space-2xl text-center shadow-card">
                <span class="flex w-14 h-14 items-center justify-center rounded-full bg-[#e8f5e9]">
                    <x-icon name="task_alt" size="28" class="text-status-success" />
                </span>
                <h2 class="font-headline-sm text-headline-sm text-text-primary">{{ __('Aucun dossier en attente') }}</h2>
                <p class="font-body-md text-body-md text-text-secondary max-w-sm">
                    {{ __('Les comptes arrivent ici une fois leurs frais d\'inscription confirmés côté serveur.') }}
                </p>
            </div>
        @endforelse

        @if ($farmers->hasPages())
            <div class="pt-space-xs">{{ $farmers->links() }}</div>
        @endif
    </div>
</div>

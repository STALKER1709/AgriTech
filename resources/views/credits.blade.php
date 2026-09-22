<x-layouts::public :title="__('Crédits photographiques')">
    <div class="flex flex-col gap-space-lg py-space-sm">
        <section class="flex flex-col gap-1">
            <span class="font-label-sm text-label-sm text-secondary uppercase tracking-wider font-semibold">
                {{ __('Mentions') }}
            </span>

            <h1 class="font-headline-lg-mobile text-headline-lg-mobile lg:font-headline-lg lg:text-headline-lg text-text-primary tracking-tight">
                {{ __('Crédits photographiques') }}
            </h1>

            <p class="font-body-md text-body-md text-text-secondary leading-snug">
                {{ __('Les photographies du jeu de démonstration viennent de dépôts d\'images libres. Elles sont enregistrées dans le dépôt et servies localement : l\'application n\'appelle aucun service distant pour les afficher.') }}
            </p>
        </section>

        @forelse ($credits as $kind => $entries)
            <section class="flex flex-col gap-space-sm">
                <h2 class="font-headline-md text-headline-md text-text-primary tracking-tight">
                    {{ \App\Support\PhotoCredits::label((string) $kind) }}
                </h2>

                <div class="flex flex-col gap-2">
                    @foreach ($entries as $entry)
                        <div class="bg-surface-container-lowest rounded-xl p-space-md shadow-card flex flex-col gap-1">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="font-body-md-bold text-body-md-bold text-text-primary">{{ $entry['title'] }}</span>
                                <x-badge variant="neutral">{{ $entry['licence'] }}</x-badge>
                            </div>

                            <span class="font-label-sm text-label-sm text-text-secondary">
                                {{ __('Auteur : :author', ['author' => $entry['author']]) }}
                            </span>

                            <a href="{{ $entry['source'] }}" rel="nofollow noopener" target="_blank"
                               class="font-label-sm text-label-sm text-primary hover:underline break-all">
                                {{ $entry['source'] }}
                            </a>
                        </div>
                    @endforeach
                </div>
            </section>
        @empty
            <x-card>
                <p class="font-body-md text-body-md text-text-secondary">
                    {{ __('Aucune photographie n\'est enregistrée dans ce dépôt.') }}
                </p>
            </x-card>
        @endforelse
    </div>
</x-layouts::public>

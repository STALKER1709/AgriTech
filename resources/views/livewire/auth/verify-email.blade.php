<x-layouts::auth :title="__('Vérification de l\'adresse e-mail')">
    <div class="flex flex-col gap-space-md">
        <x-auth-header icon="mark_email_unread" :title="__('Vérifiez votre adresse e-mail')"
                       :description="__('Cliquez sur le lien que nous venons de vous envoyer.')" />

        @if (session('status') === 'verification-link-sent')
            <x-auth-session-status :status="__('Un nouveau lien vient d\'être envoyé à l\'adresse indiquée lors de votre inscription.')" />
        @endif

        <div class="rounded-2xl bg-surface-container-lowest p-space-md shadow-raised flex flex-col gap-space-sm">
            <p class="font-label-sm text-label-sm text-text-secondary flex items-start gap-1.5">
                <x-icon name="info" size="16" class="text-primary shrink-0 mt-0.5" />
                {{ __('En local, le courrier part dans le journal : ouvrez storage/logs/laravel.log pour y trouver le lien.') }}
            </p>

            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit"
                        class="h-14 w-full rounded-full bg-primary text-on-primary font-label-lg text-label-lg flex items-center justify-center gap-2 shadow-raised hover:bg-primary-container transition-colors">
                    <x-icon name="send" size="20" />
                    {{ __('Renvoyer l\'e-mail de vérification') }}
                </button>
            </form>
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" data-test="logout-button"
                    class="w-full h-12 rounded-full bg-surface-container-low text-status-error font-label-lg text-label-lg flex items-center justify-center gap-2 hover:bg-surface-container transition-colors">
                <x-icon name="logout" size="18" />
                {{ __('Se déconnecter') }}
            </button>
        </form>
    </div>
</x-layouts::auth>

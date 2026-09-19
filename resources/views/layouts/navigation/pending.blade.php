{{-- Shown while an account is not active yet: its own area is still closed. --}}
<flux:sidebar.group :heading="__('Mon compte')" class="grid">
    <flux:sidebar.item icon="clock" :href="route('account.status')" :current="request()->routeIs('account.status')" wire:navigate>
        {{ __('Statut de mon compte') }}
    </flux:sidebar.item>
</flux:sidebar.group>

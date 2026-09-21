{{-- Bottom navigation bar from the Stitch shell: 64px, white surface over
     the ivory canvas, warm top border, active tab in forest green with a
     filled pill badge. Kept above the safe-area inset on mobile devices. --}}
<nav class="fixed inset-x-0 bottom-0 z-50 border-t border-stitch-border bg-white/95 shadow-[0_-2px_12px_rgba(31,36,33,0.05)] backdrop-blur-xl"
     style="padding-bottom: env(safe-area-inset-bottom, 0px);"
     aria-label="{{ __('Navigation principale') }}">
    <div class="mx-auto flex h-16 max-w-2xl items-center justify-around px-1 lg:hidden">
        @foreach ($tabs as $tab)
            <a href="{{ $tab['href'] }}" wire:navigate
               @class(['group flex min-w-14 flex-col items-center justify-center gap-0.5 px-1 py-1 transition-colors',
                        'text-stitch-primary' => $tab['current'],
                        'text-stitch-muted hover:text-stitch-primary' => ! $tab['current']])
               @if ($tab['current']) aria-current="page" @endif>
                <span class="relative">
                    <flux:icon name="{{ $tab['icon'] }}" class="size-6" />
                    @if ($tab['badge'])
                        <span class="absolute -right-2 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-stitch-terra px-1 text-[9px] font-bold leading-none text-white ring-2 ring-white">
                            {{ $tab['badge'] > 9 ? '9+' : $tab['badge'] }}
                        </span>
                    @endif
                </span>
                <span class="text-[11px] leading-tight {{ $tab['current'] ? 'font-bold' : 'font-medium' }}">
                    {{ $tab['label'] }}
                </span>
            </a>
        @endforeach
    </div>
</nav>

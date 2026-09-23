<div class="flex w-full flex-col gap-space-md">
    <div class="flex items-center justify-between gap-space-sm">
        <a href="{{ route('client.orders') }}" wire:navigate
           class="inline-flex items-center gap-1.5 text-text-secondary hover:text-primary transition-colors py-1">
            <x-icon name="arrow_back" size="20" />
            <span class="font-label-sm text-label-sm">{{ __('Mes commandes') }}</span>
        </a>
    </div>

    {{-- Référence — `agritech_d_tail_de_commande`. La maquette place ici un
         bouton de facture PDF : la plateforme n'en produit pas, donc pas de
         bouton qui ne mènerait nulle part. --}}
    <section class="flex flex-wrap items-center justify-between gap-space-sm bg-surface-container-low rounded-xl p-space-md shadow-card">
        <div class="flex flex-col min-w-0">
            <span class="font-label-sm text-label-sm text-text-secondary uppercase tracking-wider">
                {{ __('Référence commande') }}
            </span>
            <h1 class="font-headline-sm text-headline-sm text-text-primary">{{ $order->reference }}</h1>
            <span class="font-label-sm text-label-sm text-text-secondary">
                {{ $order->created_at?->timezone(config('app.timezone'))->translatedFormat('d F Y à H:i') }}
            </span>
        </div>

        <div class="flex flex-col items-end gap-1 shrink-0">
            <span class="font-headline-md text-headline-md text-primary" data-test="order-total">
                {{ $order->total_amount->format() }}
            </span>
            <x-badge :variant="match ($order->status) {
                \App\Enums\OrderStatus::Delivered => 'success',
                \App\Enums\OrderStatus::Cancelled => 'danger',
                \App\Enums\OrderStatus::PendingPayment => 'warning',
                default => 'primary',
            }" data-test="order-status">{{ $order->status->label() }}</x-badge>
        </div>
    </section>

    {{-- Suivi --}}
    <section class="bg-surface-container-lowest rounded-xl p-space-md shadow-card flex flex-col gap-space-md">
        <div class="flex items-center gap-space-xs">
            <x-icon name="timeline" size="20" class="text-primary" />
            <h2 class="font-label-lg text-label-lg text-text-primary">{{ __('Suivi de la commande') }}</h2>
        </div>

        <div class="relative pl-6 flex flex-col gap-space-lg">
            <div class="absolute left-[11px] top-3 bottom-3 w-[2px] bg-surface-container-highest"></div>

            @foreach ($this->timeline() as $step)
                <div @class(['relative flex items-start gap-space-md', 'opacity-50' => $step['state'] === 'todo'])>
                    <div @class([
                        'absolute -left-6 top-0 w-6 h-6 rounded-full flex items-center justify-center shadow-card',
                        'bg-status-success text-on-primary' => $step['state'] === 'done',
                        'bg-tertiary-fixed-dim text-on-tertiary-fixed' => $step['state'] === 'current',
                        'bg-surface-container-highest text-text-secondary' => $step['state'] === 'todo',
                        'bg-status-error text-on-error' => $step['state'] === 'cancelled',
                    ])>
                        <x-icon :name="$step['icon']" size="14" />
                    </div>

                    <div @class([
                        'flex flex-col min-w-0 flex-1',
                        'bg-surface-container-low p-space-sm rounded-lg' => $step['state'] === 'current',
                    ])>
                        <div class="flex items-center justify-between gap-space-sm">
                            <p @class([
                                'font-body-md-bold text-body-md-bold',
                                'text-primary' => $step['state'] === 'current',
                                'text-text-primary' => $step['state'] !== 'current',
                            ])>{{ $step['label'] }}</p>

                            @if ($step['at'])
                                <span class="font-label-sm text-label-sm text-text-secondary shrink-0">
                                    {{ $step['at']->timezone(config('app.timezone'))->translatedFormat('d M, H:i') }}
                                </span>
                            @endif
                        </div>

                        <p class="font-label-sm text-label-sm text-text-secondary">{{ $step['detail'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Paiement — `agritech_choix_du_moyen_de_paiement` --}}
    @if ($this->isAwaitingPayment())
        <section class="flex flex-col gap-space-sm">
            <div class="flex items-center justify-between px-space-xs gap-space-sm">
                <h2 class="font-headline-sm text-headline-sm text-on-surface">{{ __('Moyen de paiement mobile') }}</h2>
                <span class="font-label-sm text-label-sm text-text-secondary shrink-0">{{ __('Choisissez votre réseau') }}</span>
            </div>

            @if ($order->expires_at)
                <div class="bg-surface-container rounded-lg p-space-sm flex items-start gap-space-xs">
                    <x-icon name="schedule" size="20" class="text-primary shrink-0 mt-0.5" />
                    <p class="font-label-sm text-label-sm text-on-surface-variant leading-relaxed">
                        {{ __('À payer avant le :date, sans quoi la commande sera annulée et le stock rendu au catalogue.', [
                            'date' => $order->expires_at->timezone(config('app.timezone'))->translatedFormat('d F Y à H:i'),
                        ]) }}
                    </p>
                </div>
            @endif

            @if ($this->paymentInFlight())
                <div class="bg-surface-container rounded-lg p-space-sm flex items-start gap-space-xs">
                    <x-icon name="sync" size="20" class="text-primary shrink-0 mt-0.5" />
                    <div class="flex flex-col gap-1">
                        <p class="font-label-sm text-label-sm text-on-surface-variant">
                            {{ __('Un paiement est déjà en cours de vérification pour cette commande.') }}
                        </p>
                        <a href="{{ route('payments.pending', ['payment' => $this->paymentInFlight()->provider_reference]) }}"
                           wire:navigate class="font-label-sm text-label-sm text-primary font-semibold hover:underline">
                            {{ __('Suivre le paiement') }}
                        </a>
                    </div>
                </div>
            @endif

            {{-- Une carte par opérateur, aux couleurs de la maquette. La copie
                 ne promet aucun prompt USSD : la passerelle est simulée et
                 aucun opérateur réel n'est contacté. --}}
            @foreach ($this->methods() as $option)
                @php($selected = $method === $option->value)
                @php($mtn = $option === \App\Enums\PaymentMethod::MtnMomo)

                <button type="button" wire:click="$set('method', '{{ $option->value }}')"
                        data-test="method-{{ $option->value }}"
                        @class([
                            'relative rounded-xl p-space-md text-left transition-all duration-200 shadow-card cursor-pointer w-full',
                            'bg-[#FFF9E6]' => $mtn,
                            'bg-[#FFF5ED]' => ! $mtn,
                            'shadow-[0_4px_16px_rgba(245,158,11,0.18)]' => $selected && $mtn,
                            'shadow-[0_4px_16px_rgba(234,88,12,0.18)]' => $selected && ! $mtn,
                        ])>
                    <div class="flex items-start justify-between gap-space-sm">
                        <div class="flex items-start gap-space-sm min-w-0">
                            <div @class([
                                'w-12 h-12 rounded-full flex items-center justify-center shrink-0 shadow-card',
                                'bg-[#FEF08A] text-[#854D0E]' => $mtn,
                                'bg-[#FFEDD5] text-[#C2410C]' => ! $mtn,
                            ])>
                                <x-icon :name="$mtn ? 'contactless' : 'account_balance_wallet'" size="24" />
                            </div>

                            <div class="flex flex-col min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span @class([
                                        'font-headline-sm text-headline-sm',
                                        'text-[#451A03]' => $mtn,
                                        'text-[#431407]' => ! $mtn,
                                    ])>{{ $option->label() }}</span>

                                    <span @class([
                                        'text-surface-white font-label-sm text-label-sm font-semibold px-2 py-0.5 rounded-full',
                                        'bg-[#CA8A04]' => $mtn,
                                        'bg-[#EA580C]' => ! $mtn,
                                    ])>{{ __('Simulé') }}</span>
                                </div>

                                <span @class([
                                    'font-label-sm text-label-sm mt-0.5',
                                    'text-[#78350F]' => $mtn,
                                    'text-[#9A3412]' => ! $mtn,
                                ])>{{ __('Aucun opérateur réel n\'est contacté : vous déciderez de l\'issue sur l\'écran suivant.') }}</span>
                            </div>
                        </div>

                        <div @class([
                            'w-6 h-6 rounded-full flex items-center justify-center shrink-0 mt-1 transition-colors',
                            'bg-[#CA8A04]' => $selected && $mtn,
                            'bg-[#EA580C]' => $selected && ! $mtn,
                            'bg-[#FEF08A]' => ! $selected && $mtn,
                            'bg-[#FED7AA]' => ! $selected && ! $mtn,
                        ])>
                            @if ($selected)
                                <div class="w-2.5 h-2.5 rounded-full bg-surface-white"></div>
                            @endif
                        </div>
                    </div>
                </button>
            @endforeach

            {{-- Numéro payeur --}}
            <div class="bg-surface-container-lowest rounded-xl p-space-md shadow-card flex flex-col gap-space-sm">
                <label for="payer-phone" class="font-label-lg text-label-lg text-on-surface flex items-center justify-between gap-space-sm">
                    <span>{{ __('Numéro du compte payeur') }}</span>
                    <span class="font-label-sm text-label-sm text-secondary font-semibold shrink-0">
                        {{ \App\Enums\PaymentMethod::from($method)->label() }}
                    </span>
                </label>

                <div class="flex items-center bg-surface-container-low rounded-xl px-space-sm py-1.5 focus-within:bg-surface-white focus-within:shadow-raised transition-all">
                    <div class="flex items-center gap-1.5 pr-3 pl-1 text-on-surface select-none">
                        <span class="font-headline-sm text-headline-sm tracking-tight">+237</span>
                    </div>

                    <div class="h-6 w-0.5 bg-outline-variant mr-3"></div>

                    <input id="payer-phone" type="tel" wire:model="phone" data-test="phone"
                           placeholder="670 12 34 56"
                           class="w-full bg-transparent font-headline-sm text-headline-sm text-on-surface placeholder:text-outline outline-none tracking-wide" />
                </div>

                @error('phone')
                    <p class="flex items-center gap-1 font-label-sm text-label-sm text-status-error">
                        <x-icon name="error" size="14" />
                        {{ $message }}
                    </p>
                @enderror

                <div class="bg-surface-container rounded-lg p-space-sm flex items-start gap-space-xs mt-1">
                    <x-icon name="phonelink_ring" size="20" class="text-primary shrink-0 mt-0.5" />
                    <p class="font-label-sm text-label-sm text-on-surface-variant leading-relaxed">
                        {!! __('La passerelle de test vous demandera de choisir l\'issue du paiement de <strong>:amount</strong>. Le stock ne bouge qu\'après confirmation côté serveur.', [
                            'amount' => e($order->total_amount->format()),
                        ]) !!}
                    </p>
                </div>
            </div>

            <div class="flex flex-col gap-space-xs pt-space-xs">
                <button type="button" wire:click="pay" wire:loading.attr="disabled" data-test="pay"
                        class="w-full h-14 rounded-full bg-primary hover:bg-primary-container active:scale-[0.99] text-on-primary font-headline-sm text-headline-sm flex items-center justify-center gap-space-xs shadow-raised transition-all duration-150 cursor-pointer">
                    <x-icon name="lock" size="22" />
                    <span>{{ __('Payer :amount', ['amount' => $order->total_amount->format()]) }}</span>
                    <x-icon name="arrow_forward" size="20" class="ml-1" />
                </button>

                <div class="flex items-center justify-center gap-1.5 text-text-secondary font-label-sm text-label-sm pt-1">
                    <x-icon name="verified_user" size="16" class="text-status-success" />
                    <span>{{ __('Confirmation vérifiée côté serveur, jamais sur un simple retour du navigateur') }}</span>
                </div>
            </div>
        </section>
    @endif

    {{-- Parts des producteurs --}}
    <section class="flex flex-col gap-space-sm">
        <h2 class="font-headline-sm text-headline-sm text-text-primary">
            {{ trans_choice('{1}Votre producteur|[2,*]Vos :count producteurs', $order->subOrders->count()) }}
        </h2>

        @foreach ($order->subOrders as $subOrder)
            <div class="bg-surface-container-lowest rounded-xl shadow-card overflow-hidden" wire:key="sub-{{ $subOrder->id }}">
                <div class="bg-surface-container-low px-space-md py-space-sm flex items-center justify-between gap-space-sm">
                    <div class="flex items-center gap-space-xs min-w-0">
                        <x-icon name="verified" size="20" filled class="text-primary" />
                        <div class="min-w-0">
                            <h3 class="font-headline-sm text-headline-sm text-text-primary truncate">
                                {{ $subOrder->farmer->farmerProfile?->farm_name ?? $subOrder->farmer->name }}
                            </h3>
                            <p class="font-label-sm text-label-sm text-text-secondary truncate">{{ $subOrder->reference }}</p>
                        </div>
                    </div>

                    <x-badge :variant="match ($subOrder->status) {
                        \App\Enums\SubOrderStatus::Delivered => 'success',
                        \App\Enums\SubOrderStatus::Cancelled => 'danger',
                        \App\Enums\SubOrderStatus::Preparing => 'warning',
                        default => 'neutral',
                    }">{{ $subOrder->status->label() }}</x-badge>
                </div>

                <div class="p-space-md flex flex-col gap-space-sm">
                    @foreach ($subOrder->items as $item)
                        <div class="flex items-center gap-space-sm">
                            <div class="w-12 h-12 rounded-lg overflow-hidden shrink-0 bg-surface-container">
                                @if ($item->product->images->isNotEmpty())
                                    <img src="{{ $item->product->images->first()->url() }}" alt="" loading="lazy"
                                         class="w-full h-full object-cover" />
                                @endif
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="font-body-md-bold text-body-md-bold text-text-primary truncate">{{ $item->product->name }}</p>
                                <p class="font-label-sm text-label-sm text-text-secondary">
                                    {{ $item->quantity->format() }} {{ $item->product->unit->countLabel($item->quantity) }}
                                    × {{ $item->unit_price_snapshot->format() }}
                                </p>
                            </div>

                            <span class="font-body-md-bold text-body-md-bold text-text-primary shrink-0">
                                {{ $item->line_total->format() }}
                            </span>
                        </div>
                    @endforeach
                </div>

                <div class="bg-surface-container-low/60 px-space-md py-space-xs flex items-center justify-between gap-space-sm">
                    <span class="font-label-sm text-label-sm text-text-secondary">{{ __('Sous-total') }}</span>
                    <span class="font-body-md-bold text-body-md-bold text-text-primary">{{ $subOrder->subtotal_amount->format() }}</span>
                </div>
            </div>
        @endforeach
    </section>
</div>

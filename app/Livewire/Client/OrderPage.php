<?php

declare(strict_types=1);

namespace App\Livewire\Client;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SubOrderStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\SubOrder;
use App\Models\User;
use App\Rules\CameroonPhoneNumber;
use App\Services\Orders\OrderPaymentService;
use App\Support\Money;
use App\Support\PhoneNumber;
use Carbon\CarbonInterface;
use DomainException;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * One order, with the payment form while it is still owed.
 *
 * The page can start a payment; it cannot finish one. Business rule RG06
 * keeps confirmation on the server side, so what is shown here is only ever
 * what the server has already recorded.
 */
class OrderPage extends Component
{
    public Order $order;

    public string $method = PaymentMethod::MtnMomo->value;

    public string $phone = '';

    public function mount(Order $order): void
    {
        $this->authorize('view', $order);

        $this->order = $order->load(['subOrders.farmer.farmerProfile', 'subOrders.items.product']);

        // Le champ est précédé d'un badge « +237 » verrouillé : n'y remettre
        // que la partie nationale, sinon l'indicatif apparaît deux fois.
        $user = Auth::user();
        $this->phone = $user instanceof User
            ? (PhoneNumber::tryParse($user->phone)?->format() ?? '')
            : '';
    }

    #[Computed]
    public function isAwaitingPayment(): bool
    {
        return $this->order->status === OrderStatus::PendingPayment;
    }

    #[Computed]
    public function paymentInFlight(): ?Payment
    {
        return app(OrderPaymentService::class)->paymentInFlight($this->order);
    }

    #[Computed]
    public function commission(): Money
    {
        return Money::sum($this->order->subOrders->map(fn ($subOrder): Money => $subOrder->commission_amount));
    }

    /**
     * The order's own history, for the timeline of the mockup.
     *
     * Every step is read from something that actually happened: the order's
     * creation, the confirmation date of its succeeded payment, the moment
     * its farmers moved their share along. The mockup also draws a transit
     * step and a delivery agency; the platform arranges neither, so neither
     * appears here.
     *
     * @return array<int, array{label: string, detail: string, at: ?CarbonInterface, state: string, icon: string}>
     */
    public function timeline(): array
    {
        $paid = $this->order->payments()
            ->where('status', PaymentStatus::Succeeded)
            ->latest('confirmed_at')
            ->first();

        $subOrders = $this->order->subOrders;
        $preparing = $subOrders->whereIn('status', [SubOrderStatus::Preparing, SubOrderStatus::Delivered]);
        $delivered = $subOrders->where('status', SubOrderStatus::Delivered);

        $steps = [
            [
                'label' => (string) __('Commande créée'),
                'detail' => (string) __('Panier validé, prix figés'),
                'at' => $this->order->created_at,
                'state' => 'done',
                'icon' => 'check',
            ],
            [
                'label' => $paid !== null
                    ? (string) __('Payée par :method', ['method' => $paid->method->label()])
                    : (string) __('Paiement'),
                'detail' => $paid !== null
                    ? (string) __('Paiement de :amount confirmé par l\'opérateur', ['amount' => $paid->amount->format()])
                    : (string) __('En attente de la confirmation de l\'opérateur'),
                'at' => $paid?->confirmed_at,
                'state' => match (true) {
                    $paid !== null => 'done',
                    $this->order->status === OrderStatus::Cancelled => 'cancelled',
                    default => 'current',
                },
                'icon' => $paid !== null ? 'check' : 'schedule',
            ],
        ];

        if ($this->order->status === OrderStatus::Cancelled) {
            $steps[] = [
                'label' => (string) __('Annulée'),
                'detail' => (string) __('Cette commande n\'ira pas plus loin'),
                'at' => $this->order->updated_at,
                'state' => 'cancelled',
                'icon' => 'close',
            ];

            return $steps;
        }

        $steps[] = [
            'label' => (string) __('En préparation'),
            'detail' => (string) trans_choice(
                '{0}Les producteurs n\'ont pas encore commencé|{1}:count producteur prépare votre commande|[2,*]:count producteurs préparent votre commande',
                $preparing->count(),
            ),
            'at' => self::latestUpdate($preparing),
            'state' => match (true) {
                $subOrders->isNotEmpty() && $delivered->count() === $subOrders->count() => 'done',
                $preparing->isNotEmpty() => 'current',
                default => 'todo',
            },
            'icon' => 'inventory_2',
        ];

        $steps[] = [
            'label' => (string) __('Livrée'),
            'detail' => (string) trans_choice(
                '{0}Aucun producteur n\'a encore livré|{1}:count producteur a livré|[2,*]:count producteurs ont livré',
                $delivered->count(),
            ),
            'at' => $delivered->count() === $subOrders->count() ? self::latestUpdate($delivered) : null,
            'state' => $this->order->status === OrderStatus::Delivered ? 'done' : 'todo',
            'icon' => 'handshake',
        ];

        return $steps;
    }

    /**
     * The most recent update among a set of sub-orders.
     *
     * Written out rather than reached for with max(), which hands back a
     * mixed value and would leave the step's date untyped.
     *
     * @param  Collection<int, SubOrder>  $subOrders
     */
    private static function latestUpdate(Collection $subOrders): ?CarbonInterface
    {
        $latest = null;

        foreach ($subOrders as $subOrder) {
            $at = $subOrder->updated_at;

            if ($at !== null && ($latest === null || $at->greaterThan($latest))) {
                $latest = $at;
            }
        }

        return $latest;
    }

    /**
     * @return array<int, PaymentMethod>
     */
    public function methods(): array
    {
        return PaymentMethod::cases();
    }

    /**
     * Start the payment. Note what is not sent: the amount, which the server
     * reads from the order itself.
     */
    public function pay(OrderPaymentService $payments): void
    {
        $this->authorize('pay', $this->order);

        $this->validate([
            'phone' => ['required', 'string', new CameroonPhoneNumber],
            'method' => ['required', 'string', 'in:'.implode(',', array_column(PaymentMethod::cases(), 'value'))],
        ]);

        try {
            $redirect = $payments->start(
                order: $this->order,
                method: PaymentMethod::from($this->method),
                payerNumber: PhoneNumber::parse($this->phone),
            );
        } catch (DomainException $exception) {
            Flux::toast(variant: 'danger', text: $exception->getMessage());

            return;
        }

        $this->redirect($redirect->url, navigate: true);
    }

    public function title(): string
    {
        return __('Commande :reference', ['reference' => $this->order->reference]);
    }

    public function render(): mixed
    {
        return view('livewire.client.order-page')->title($this->title());
    }
}

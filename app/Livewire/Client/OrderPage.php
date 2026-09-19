<?php

declare(strict_types=1);

namespace App\Livewire\Client;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Rules\CameroonPhoneNumber;
use App\Services\Orders\OrderPaymentService;
use App\Support\Money;
use App\Support\PhoneNumber;
use DomainException;
use Flux\Flux;
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

        $user = Auth::user();
        $this->phone = $user instanceof User ? $user->phone : '';
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

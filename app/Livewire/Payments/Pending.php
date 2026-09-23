<?php

declare(strict_types=1);

namespace App\Livewire\Payments;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Training;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The screen the payer lands on after deciding.
 *
 * It says the payment is being checked — never that it succeeded. Business
 * rule RG06 forbids a browser redirect from granting anything, so this page
 * has no power of its own: it polls the payment and reports what the server
 * has confirmed, if anything.
 */
#[Layout('layouts::auth')]
#[Title('Vérification du paiement')]
class Pending extends Component
{
    public Payment $payment;

    public function mount(Payment $payment): void
    {
        abort_unless($payment->user_id === Auth::id(), 403);

        $this->payment = $payment;
    }

    /**
     * Polled by the view. Reads the payment, decides nothing.
     */
    public function refreshStatus(): void
    {
        $this->payment = $this->payment->fresh() ?? $this->payment;
    }

    public function isSettled(): bool
    {
        return ! $this->payment->status->isAwaitingOutcome();
    }

    public function hasSucceeded(): bool
    {
        return $this->payment->status === PaymentStatus::Succeeded;
    }

    /**
     * Where to send the payer once there is something to tell them.
     *
     * Keyed on what was being paid for, so that adding a purpose in a later
     * phase means adding a case here rather than discovering that everyone
     * lands on the farmer's status screen.
     */
    public function continueUrl(): string
    {
        $payable = $this->payment->payable;

        if ($payable instanceof Order) {
            return route('client.orders.show', ['order' => $payable->reference]);
        }

        if ($payable instanceof Training) {
            return route('trainings.show', ['training' => $payable->slug]);
        }

        if ($payable instanceof Subscription) {
            return route('client.subscriptions');
        }

        return route('account.status');
    }

    /**
     * The wording of the button that leads there. It is built from the same
     * `payable` as the destination: a subscription payment offering to follow
     * "ma commande" would promise a page that does not exist for it.
     *
     * @return array{label: string, icon: string}
     */
    public function continueAction(): array
    {
        $payable = $this->payment->payable;

        return match (true) {
            $payable instanceof Order => ['label' => (string) __('Suivre ma commande'), 'icon' => 'local_shipping'],
            $payable instanceof Training => ['label' => (string) __('Ouvrir la formation'), 'icon' => 'school'],
            $payable instanceof Subscription => ['label' => (string) __('Voir mon abonnement'), 'icon' => 'card_membership'],
            default => ['label' => (string) __('Voir mon compte'), 'icon' => 'account_circle'],
        };
    }

    public function render(): mixed
    {
        return view('livewire.payments.pending');
    }
}

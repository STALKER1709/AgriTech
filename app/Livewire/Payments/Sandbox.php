<?php

declare(strict_types=1);

namespace App\Livewire\Payments;

use App\Enums\PaymentStatus;
use App\Jobs\DeliverSimulatedCallback;
use App\Models\Payment;
use App\Payments\Gateways\FakeMobileMoneyGateway;
use App\Rules\CameroonPhoneNumber;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The simulated operator's payment page.
 *
 * It stands in for the hosted checkout a real operator would show: the amount,
 * the chosen operator, a phone number, and a decision. Three buttons cover the
 * outcomes a real payment can take, including the two an operator makes hard
 * to trigger on demand — a refusal and an expiry.
 *
 * Choosing an outcome here does not change the payment. It only queues the
 * callback, exactly as an operator would answer out of band. Business rule
 * RG06 means the payment moves when the webhook says so, not when this page
 * is clicked.
 */
#[Layout('layouts::auth')]
#[Title('Paiement (environnement de test)')]
class Sandbox extends Component
{
    public Payment $payment;

    public string $phone = '';

    public bool $decisionTaken = false;

    public function mount(Payment $payment): void
    {
        // A payment page is personal: only its payer may open it.
        abort_unless($payment->user_id === Auth::id(), 403);

        $this->payment = $payment;
        $this->phone = $payment->user->phone;

        if (! $payment->status->isAwaitingOutcome()) {
            $this->redirectRoute('payments.pending', ['payment' => $payment->provider_reference], navigate: true);
        }
    }

    public function confirm(): void
    {
        $this->decide(PaymentStatus::Succeeded);
    }

    public function refuse(): void
    {
        $this->decide(PaymentStatus::Failed);
    }

    /**
     * Walk away without answering, which is what an expiry actually is: the
     * reconciliation task closes the payment later.
     */
    public function abandon(): void
    {
        $this->decisionTaken = true;

        $this->redirectRoute('payments.pending', ['payment' => $this->payment->provider_reference], navigate: true);
    }

    private function decide(PaymentStatus $chosen): void
    {
        $this->validate([
            'phone' => ['required', 'string', new CameroonPhoneNumber],
        ]);

        $number = PhoneNumber::parse($this->phone);

        // Some numbers override the button, so that a refusal or an expiry can
        // be replayed from a script without a human clicking the right one.
        /** @var array<string, string> $forced */
        $forced = config('payments.test_numbers', []);
        $outcome = isset($forced[$number->toE164()])
            ? PaymentStatus::from($forced[$number->toE164()])
            : $chosen;

        $this->decisionTaken = true;

        // An expiry is the absence of an answer, so nothing is sent at all.
        if ($outcome !== PaymentStatus::Expired) {
            DeliverSimulatedCallback::dispatch(
                $this->payment->provider_reference,
                $outcome,
                FakeMobileMoneyGateway::newEventId(),
            )->delay(now()->addSeconds((int) config('payments.callback_delay_seconds', 5)));
        }

        $this->redirectRoute('payments.pending', ['payment' => $this->payment->provider_reference], navigate: true);
    }

    public function render(): mixed
    {
        return view('livewire.payments.sandbox');
    }
}

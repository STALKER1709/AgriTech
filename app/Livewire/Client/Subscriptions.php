<?php

declare(strict_types=1);

namespace App\Livewire\Client;

use App\Enums\PaymentMethod;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Rules\CameroonPhoneNumber;
use App\Services\Subscriptions\SubscriptionService;
use App\Support\PhoneNumber;
use DomainException;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The client's subscription screen: the plan in force, the plans on offer,
 * and the form that starts a subscription payment.
 *
 * Like every payment screen, it can start a payment and cannot grant
 * anything: the term opens when the webhook confirms, not when this form
 * is submitted.
 */
#[Title('Mon abonnement')]
class Subscriptions extends Component
{
    public bool $showPlans = false;

    public ?int $selected_plan_id = null;

    public string $method = PaymentMethod::MtnMomo->value;

    public string $phone = '';

    public function mount(): void
    {
        $user = Auth::user();

        abort_unless($user instanceof User && $user->isClient(), 403);

        $this->phone = $user->phone;
    }

    public function current(): ?Subscription
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return null;
        }

        return app(SubscriptionService::class)->runningSubscription($user);
    }

    /**
     * @return Collection<int, SubscriptionPlan>
     */
    public function plans(): Collection
    {
        return SubscriptionPlan::query()->active()->orderBy('price')->get();
    }

    public function selectPlan(int $planId): void
    {
        $this->selected_plan_id = $planId;
        $this->showPlans = true;
    }

    public function subscribe(SubscriptionService $subscriptions): void
    {
        $user = Auth::user();

        abort_unless($user instanceof User && $user->isClient(), 403);

        $this->validate([
            'selected_plan_id' => ['required', 'integer', 'exists:subscription_plans,id'],
            'phone' => ['required', 'string', new CameroonPhoneNumber],
            'method' => ['required', 'string', 'in:'.implode(',', array_column(PaymentMethod::cases(), 'value'))],
        ], [], [
            'selected_plan_id' => __('plan'),
        ]);

        $plan = SubscriptionPlan::query()->findOrFail((int) $this->selected_plan_id);

        try {
            $redirect = $subscriptions->start(
                plan: $plan,
                client: $user,
                method: PaymentMethod::from($this->method),
                payerNumber: PhoneNumber::parse($this->phone),
            );
        } catch (DomainException $exception) {
            Flux::toast(variant: 'danger', text: $exception->getMessage());

            return;
        }

        $this->redirect($redirect->url, navigate: true);
    }

    public function render(): mixed
    {
        return view('livewire.client.subscriptions');
    }
}

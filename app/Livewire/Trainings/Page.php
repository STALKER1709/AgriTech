<?php

declare(strict_types=1);

namespace App\Livewire\Trainings;

use App\Enums\PaymentMethod;
use App\Models\Training;
use App\Models\User;
use App\Rules\CameroonPhoneNumber;
use App\Services\Trainings\TrainingPaymentService;
use App\Support\PhoneNumber;
use DomainException;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * One training's public page, with the purchase form when it can be bought.
 *
 * The page can start a payment; it cannot grant access. Business rule RG06
 * keeps confirmation on the server side, and RG05 keeps entitlement with the
 * purchase row or the subscription, both read from the database.
 */
#[Layout('layouts::public')]
class Page extends Component
{
    public Training $training;

    public string $method = PaymentMethod::MtnMomo->value;

    public string $phone = '';

    public function mount(Training $training): void
    {
        // A draft, a training in review, or one whose farmer is suspended is
        // not public. Answering 404 rather than 403 keeps it from confirming
        // that the training exists at all.
        abort_unless(
            Training::query()->visibleToPublic()->whereKey($training->id)->exists(),
            404,
        );

        $this->training = $training->load('farmer.farmerProfile');

        $user = Auth::user();
        $this->phone = $user instanceof User ? $user->phone : '';
    }

    /**
     * Whether the person reading already owns the content.
     */
    public function hasAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $this->training->isAccessibleBy($user);
    }

    /**
     * Whether the person reading can actually buy: browsing is open to all,
     * buying is an active client's.
     */
    public function canBuy(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->isClient() && $user->isActive();
    }

    /**
     * Content the visitor has not bought is described, never linked: the
     * titles of the modules are shown, the files behind them are not.
     */
    public function isVisitor(): bool
    {
        return ! Auth::check();
    }

    public function buy(TrainingPaymentService $payments): void
    {
        $user = Auth::user();

        // A visitor is sent to log in and comes straight back here.
        if (! $user instanceof User) {
            session(['url.intended' => route('trainings.show', ['training' => $this->training->slug])]);

            $this->redirectRoute('login', navigate: true);

            return;
        }

        abort_unless($user->isClient() && $user->isActive(), 403);

        $this->validate([
            'phone' => ['required', 'string', new CameroonPhoneNumber],
            'method' => ['required', 'string', 'in:'.implode(',', array_column(PaymentMethod::cases(), 'value'))],
        ]);

        try {
            $redirect = $payments->start(
                training: $this->training,
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

    public function title(): string
    {
        return $this->training->title;
    }

    public function render(): mixed
    {
        return view('livewire.trainings.page')->title($this->training->title);
    }
}

<?php

declare(strict_types=1);

namespace App\Livewire\Account;

use App\Enums\PaymentMethod;
use App\Enums\UserStatus;
use App\Models\User;
use App\Rules\CameroonPhoneNumber;
use App\Services\Payments\RegistrationFeeService;
use App\Support\Money;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Tells a user whose account is not active yet where their file stands.
 *
 * For a farmer who still owes the registration fee, this is also where the
 * payment starts: business rule RG02 makes that payment the only way the
 * account moves on to review.
 */
#[Title('Statut de mon compte')]
class Status extends Component
{
    public string $method = PaymentMethod::MtnMomo->value;

    public string $phone = '';

    public function mount(): void
    {
        $user = Auth::user();

        // An active account has no waiting to report; send it to its area.
        if ($user instanceof User && $user->isActive()) {
            $this->redirectRoute('dashboard', navigate: true);

            return;
        }

        // Le champ est précédé d'un badge « +237 » verrouillé : n'y remettre
        // que la partie nationale, sinon l'indicatif apparaît deux fois.
        $this->phone = $user instanceof User
            ? (PhoneNumber::tryParse($user->phone)?->format() ?? '')
            : '';
    }

    /**
     * L'avancement du dossier, tel que la maquette le dessine : quatre
     * étapes, chacune lue sur ce que la base sait vraiment.
     *
     * @return array<int, array{icon: string, label: string, detail: string, state: string}>
     */
    #[Computed]
    public function steps(): array
    {
        $user = $this->user();
        $paid = ! $this->awaitsPayment();
        $validated = $user->isActive();
        $rejected = $this->wasRejected();

        return [
            [
                'icon' => 'assignment_turned_in',
                'label' => (string) __('Inscription enregistrée'),
                'detail' => (string) __('Le :date', [
                    'date' => $user->created_at?->timezone(config('app.timezone'))->translatedFormat('d F Y à H:i'),
                ]),
                'state' => 'done',
            ],
            [
                'icon' => 'payments',
                'label' => $paid
                    ? (string) __('Frais d\'inscription réglés')
                    : (string) __('Frais d\'inscription'),
                'detail' => $paid
                    ? (string) __(':amount reçus et vérifiés côté serveur', ['amount' => $this->registrationFee()->format()])
                    : (string) __(':amount à régler', ['amount' => $this->registrationFee()->format()]),
                'state' => $paid ? 'done' : 'current',
            ],
            [
                'icon' => $rejected ? 'gpp_bad' : 'fact_check',
                'label' => (string) __('Vérification du dossier'),
                'detail' => match (true) {
                    $rejected => (string) __('Dossier refusé'),
                    $validated => (string) __('Dossier accepté'),
                    $paid => (string) __('Un administrateur examine votre exploitation'),
                    default => (string) __('Commence une fois les frais réglés'),
                },
                'state' => match (true) {
                    $rejected => 'rejected',
                    $validated => 'done',
                    $paid => 'current',
                    default => 'todo',
                },
            ],
            [
                'icon' => 'storefront',
                'label' => (string) __('Ouverture de votre boutique'),
                'detail' => (string) __('Publier vos produits et vos formations'),
                'state' => $validated ? 'done' : 'todo',
            ],
        ];
    }

    /**
     * La part du parcours franchie, en pourcentage. Ce n'est pas une
     * estimation : c'est le compte des étapes réellement acquises.
     */
    #[Computed]
    public function progress(): int
    {
        $steps = $this->steps();
        $done = count(array_filter($steps, fn (array $step): bool => $step['state'] === 'done'));

        return (int) round($done / count($steps) * 100);
    }

    #[Computed]
    public function user(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }

    #[Computed]
    public function registrationFee(): Money
    {
        return app(RegistrationFeeService::class)->amountDue();
    }

    #[Computed]
    public function awaitsPayment(): bool
    {
        return $this->user()->status === UserStatus::PendingPayment;
    }

    #[Computed]
    public function awaitsValidation(): bool
    {
        return $this->user()->status === UserStatus::PendingValidation;
    }

    #[Computed]
    public function wasRejected(): bool
    {
        return $this->user()->status === UserStatus::Rejected;
    }

    #[Computed]
    public function rejectionReason(): ?string
    {
        return $this->user()->farmerProfile?->rejection_reason;
    }

    /**
     * @return array<int, PaymentMethod>
     */
    public function methods(): array
    {
        return PaymentMethod::cases();
    }

    /**
     * Start the fee payment and hand the payer over to the gateway.
     *
     * Note what is not passed: the amount. It is read server-side from the
     * settings, so a tampered form changes nothing.
     */
    public function payRegistrationFee(RegistrationFeeService $fees): void
    {
        $this->validate([
            'phone' => ['required', 'string', new CameroonPhoneNumber],
            'method' => ['required', 'string', 'in:'.implode(',', array_column(PaymentMethod::cases(), 'value'))],
        ]);

        $redirect = $fees->start(
            farmer: $this->user(),
            method: PaymentMethod::from($this->method),
            payerNumber: PhoneNumber::parse($this->phone),
        );

        $this->redirect($redirect->url, navigate: true);
    }

    public function render(): mixed
    {
        return view('livewire.account.status');
    }
}

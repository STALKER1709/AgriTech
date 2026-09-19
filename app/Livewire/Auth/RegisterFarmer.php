<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Services\Auth\FarmerRegistrar;
use App\Support\CameroonRegions;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::auth')]
#[Title('Inscription agriculteur')]
class RegisterFarmer extends Component
{
    use PasswordValidationRules, ProfileValidationRules;

    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public string $phone = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $farm_name = '';

    public string $region = '';

    public string $city = '';

    public string $description = '';

    /**
     * @return array<int, string>
     */
    public function regions(): array
    {
        return CameroonRegions::all();
    }

    /**
     * Create the account, sign the farmer in, and send them to the screen that
     * explains the registration fee.
     *
     * The account is deliberately not active yet: business rule RG02 makes a
     * successful fee payment the only way out of PendingPayment.
     */
    public function register(FarmerRegistrar $registrar): void
    {
        $validated = $this->validate([
            ...$this->profileRules(),
            ...$this->farmRules(),
            'password' => $this->passwordRules(),
        ]);

        $farmer = $registrar->register([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => $validated['password'],
            'farm_name' => $validated['farm_name'],
            'region' => $validated['region'],
            'city' => $validated['city'],
            'description' => $validated['description'] ?? null,
        ]);

        Auth::login($farmer);

        $this->redirectIntended(route('account.status', absolute: false), navigate: true);
    }

    public function render(): mixed
    {
        return view('livewire.auth.register-farmer');
    }
}

<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post(route('register.store'), [
            'first_name' => 'Clarisse',
            'last_name' => 'Etoundi',
            'email' => 'test@example.com',
            'phone' => '+237650000123',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();

        $user = User::query()->where('email', 'test@example.com')->sole();

        // Registration through this form creates clients. Farmer sign-up
        // carries a profile and a registration fee, and has its own flow.
        $this->assertSame(UserRole::Client, $user->role);
        $this->assertSame(UserStatus::Active, $user->status);
        $this->assertSame('Clarisse Etoundi', $user->name);
    }
}

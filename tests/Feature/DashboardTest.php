<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    /**
     * The dashboard is a signpost rather than a page: it sends each role to
     * its own area.
     */
    public function test_authenticated_users_are_sent_to_their_own_area(): void
    {
        $this->actingAs(User::factory()->client()->create());
        $this->get(route('dashboard'))->assertRedirect(route('client.dashboard'));

        $this->actingAs(User::factory()->farmer()->create());
        $this->get(route('dashboard'))->assertRedirect(route('farmer.dashboard'));

        $this->actingAs(User::factory()->admin()->create());
        $this->get(route('dashboard'))->assertRedirect(route('admin.dashboard'));
    }

    public function test_an_account_that_is_not_active_is_sent_to_the_status_screen(): void
    {
        $this->actingAs(User::factory()->awaitingPayment()->create());

        $this->get(route('dashboard'))->assertRedirect(route('account.status'));
    }
}

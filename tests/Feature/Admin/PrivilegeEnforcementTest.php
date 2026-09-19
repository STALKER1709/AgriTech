<?php

declare(strict_types=1);

use App\Livewire\Admin\AuditTrail;
use App\Livewire\Admin\FarmerValidation;
use App\Livewire\Admin\Privileges as PrivilegesScreen;
use App\Livewire\Admin\Settings as SettingsScreen;
use App\Livewire\Admin\Users as UsersScreen;
use App\Models\FarmerProfile;
use App\Models\Privilege;
use App\Models\User;
use Database\Seeders\PrivilegeSeeder;
use Database\Seeders\SettingSeeder;
use Livewire\Livewire;

/**
 * Business rule RG07: the admin role opens the area, a privilege authorises
 * the action. Every action is checked both ways — granted and withheld.
 */
beforeEach(function () {
    $this->seed(PrivilegeSeeder::class);
});

function adminWith(string ...$codes): User
{
    $admin = User::factory()->admin()->create();

    $admin->privileges()->attach(
        Privilege::query()->whereIn('code', $codes)->pluck('id'),
    );

    return $admin->load('privileges');
}

function farmerAwaitingDecision(): User
{
    $farmer = User::factory()->awaitingValidation()->create();
    FarmerProfile::factory()->create(['user_id' => $farmer->id]);

    return $farmer;
}

describe('approving a farmer', function () {
    it('is allowed with the privilege', function () {
        $farmer = farmerAwaitingDecision();

        Livewire::actingAs(adminWith(Privilege::APPROVE_FARMERS))
            ->test(FarmerValidation::class)
            ->call('approve', $farmer->id)
            ->assertHasNoErrors();

        expect($farmer->refresh()->isActive())->toBeTrue();
    });

    it('is refused without it', function () {
        $farmer = farmerAwaitingDecision();

        Livewire::actingAs(adminWith(Privilege::SUSPEND_USERS))
            ->test(FarmerValidation::class)
            ->call('approve', $farmer->id)
            ->assertForbidden();

        expect($farmer->refresh()->isActive())->toBeFalse();
    });

    it('is refused on an account that is not awaiting a decision', function () {
        $farmer = User::factory()->awaitingPayment()->create();

        Livewire::actingAs(adminWith(Privilege::APPROVE_FARMERS))
            ->test(FarmerValidation::class)
            ->call('approve', $farmer->id)
            ->assertForbidden();
    });
});

describe('rejecting a farmer', function () {
    it('is refused without the privilege', function () {
        $farmer = farmerAwaitingDecision();

        Livewire::actingAs(adminWith(Privilege::DELETE_USERS))
            ->test(FarmerValidation::class)
            ->call('startRejection', $farmer->id)
            ->set('reason', 'Justificatifs illisibles et incomplets.')
            ->call('reject')
            ->assertForbidden();
    });

    it('demands a reason worth reading', function () {
        $farmer = farmerAwaitingDecision();

        Livewire::actingAs(adminWith(Privilege::APPROVE_FARMERS))
            ->test(FarmerValidation::class)
            ->call('startRejection', $farmer->id)
            ->set('reason', 'non')
            ->call('reject')
            ->assertHasErrors('reason');

        expect($farmer->refresh()->status->value)->toBe('pending_validation');
    });
});

describe('suspending', function () {
    it('is allowed with the privilege', function () {
        $target = User::factory()->client()->create();

        Livewire::actingAs(adminWith(Privilege::SUSPEND_USERS))
            ->test(UsersScreen::class)
            ->call('suspend', $target->id);

        expect($target->refresh()->status->value)->toBe('suspended');
    });

    it('is refused without it', function () {
        $target = User::factory()->client()->create();

        Livewire::actingAs(adminWith(Privilege::APPROVE_FARMERS))
            ->test(UsersScreen::class)
            ->call('suspend', $target->id)
            ->assertForbidden();

        expect($target->refresh()->isActive())->toBeTrue();
    });

    it('never lets an administrator suspend themselves', function () {
        $admin = adminWith(Privilege::SUSPEND_USERS);

        Livewire::actingAs($admin)
            ->test(UsersScreen::class)
            ->call('suspend', $admin->id)
            ->assertForbidden();

        expect($admin->refresh()->isActive())->toBeTrue();
    });
});

describe('deleting', function () {
    it('is refused without the privilege', function () {
        $target = User::factory()->client()->create();

        Livewire::actingAs(adminWith(Privilege::SUSPEND_USERS))
            ->test(UsersScreen::class)
            ->call('confirmDeletion', $target->id)
            ->call('delete')
            ->assertForbidden();

        expect($target->refresh()->isAnonymised())->toBeFalse();
    });

    it('never lets an administrator delete themselves', function () {
        $admin = adminWith(Privilege::DELETE_USERS);
        User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(UsersScreen::class)
            ->call('confirmDeletion', $admin->id)
            ->call('delete')
            ->assertForbidden();

        expect($admin->refresh()->isAnonymised())->toBeFalse();
    });

    it('deletes an administrator while another active one remains', function () {
        $actor = adminWith(Privilege::DELETE_USERS);
        $other = User::factory()->admin()->create();

        Livewire::actingAs($actor)
            ->test(UsersScreen::class)
            ->call('confirmDeletion', $other->id)
            ->call('delete');

        expect($other->refresh()->isAnonymised())->toBeTrue();
    });

    it('never deletes the last active administrator', function () {
        // Through the screen this can never come up: the actor is always an
        // active administrator, so excluding the target there is always at
        // least one left. The guard exists for callers that are not the
        // screen — a console command, a seeder — so it is tested where it can
        // actually be reached.
        $suspendedActor = adminWith(Privilege::DELETE_USERS);
        $suspendedActor->suspend();

        $lastActive = User::factory()->admin()->create();

        expect($suspendedActor->can('delete', $lastActive))->toBeFalse();

        // With a second active administrator, the same target may go.
        User::factory()->admin()->create();

        expect($suspendedActor->can('delete', $lastActive))->toBeTrue();
    });
});

describe('privileges screen', function () {
    it('is closed to an administrator without the privilege', function () {
        Livewire::actingAs(adminWith(Privilege::SUSPEND_USERS))
            ->test(PrivilegesScreen::class)
            ->assertForbidden();
    });

    it('refuses the edit itself, not only the screen', function () {
        // Privileges belong to administrators. Pointing the action at anyone
        // else is refused by the policy, not merely absent from the list.
        $client = User::factory()->client()->create();

        Livewire::actingAs(adminWith(Privilege::MANAGE_PRIVILEGES))
            ->test(PrivilegesScreen::class)
            ->call('edit', $client->id)
            ->assertForbidden();
    });

    it('never lets an administrator edit their own privileges', function () {
        $admin = adminWith(Privilege::MANAGE_PRIVILEGES);

        Livewire::actingAs($admin)
            ->test(PrivilegesScreen::class)
            ->call('edit', $admin->id)
            ->assertForbidden();
    });

    it('grants and revokes for another administrator', function () {
        $target = User::factory()->admin()->create();

        Livewire::actingAs(adminWith(Privilege::MANAGE_PRIVILEGES))
            ->test(PrivilegesScreen::class)
            ->call('edit', $target->id)
            ->set('selected', [Privilege::APPROVE_FARMERS, Privilege::VIEW_AUDIT_LOG])
            ->call('save')
            ->assertHasNoErrors();

        expect($target->load('privileges')->privileges->pluck('code')->sort()->values()->all())
            ->toBe([Privilege::VIEW_AUDIT_LOG, Privilege::APPROVE_FARMERS]);
    });

    it('refuses a privilege code that does not exist', function () {
        $target = User::factory()->admin()->create();

        Livewire::actingAs(adminWith(Privilege::MANAGE_PRIVILEGES))
            ->test(PrivilegesScreen::class)
            ->call('edit', $target->id)
            ->set('selected', ['tout.pouvoir'])
            ->call('save')
            ->assertHasErrors('selected.0');
    });
});

describe('settings and audit screens', function () {
    it('refuse an administrator without the privilege', function () {
        $this->seed(SettingSeeder::class);
        $admin = adminWith(Privilege::APPROVE_FARMERS);

        Livewire::actingAs($admin)->test(SettingsScreen::class)->assertForbidden();
        Livewire::actingAs($admin)->test(AuditTrail::class)->assertForbidden();
    });

    it('open for an administrator who holds it', function () {
        $this->seed(SettingSeeder::class);

        Livewire::actingAs(adminWith(Privilege::MANAGE_SETTINGS))
            ->test(SettingsScreen::class)
            ->assertOk();

        Livewire::actingAs(adminWith(Privilege::VIEW_AUDIT_LOG))
            ->test(AuditTrail::class)
            ->assertOk();
    });
});

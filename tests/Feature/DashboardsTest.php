<?php

declare(strict_types=1);

use App\Enums\SubOrderStatus;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Farmer\Dashboard as FarmerDashboard;
use App\Models\Product;
use App\Models\SubOrder;
use App\Models\Training;
use App\Models\User;
use Database\Seeders\SettingSeeder;
use Livewire\Livewire;

/**
 * Phase 10: the two dashboards read what their owner actually owns, and
 * nothing beyond it.
 */
beforeEach(function () {
    $this->seed(SettingSeeder::class);
});

describe('farmer dashboard', function () {
    it('sums the farmer\'s paid work and the commission taken from it', function () {
        $farmer = sellingFarmer();

        SubOrder::factory()->create([
            'farmer_id' => $farmer->id,
            'status' => SubOrderStatus::Delivered,
            'subtotal_amount' => 10_000,
            'commission_amount' => 500,
        ]);
        SubOrder::factory()->create([
            'farmer_id' => $farmer->id,
            'status' => SubOrderStatus::Paid,
            'subtotal_amount' => 4_000,
            'commission_amount' => 200,
        ]);
        // An unpaid or cancelled share never was work.
        SubOrder::factory()->create([
            'farmer_id' => $farmer->id,
            'status' => SubOrderStatus::Cancelled,
            'subtotal_amount' => 99_000,
            'commission_amount' => 990,
        ]);

        // Money formatting groups thousands and binds FCFA with U+00A0.
        Livewire::actingAs($farmer)
            ->test(FarmerDashboard::class)
            ->assertSee("14\u{00A0}000\u{00A0}FCFA");
    });

    it('lists the sub-orders waiting to be prepared', function () {
        $farmer = sellingFarmer();

        SubOrder::factory()->count(2)->create([
            'farmer_id' => $farmer->id,
            'status' => SubOrderStatus::Paid,
        ]);

        Livewire::actingAs($farmer)
            ->test(FarmerDashboard::class)
            ->assertSee('2', false);
    });

    it('counts the farmer\'s published catalogue', function () {
        $farmer = sellingFarmer();

        Product::factory()->published()->forFarmer($farmer)->create();
        Product::factory()->draft()->forFarmer($farmer)->create();
        Training::factory()->published()->forFarmer($farmer)->create();

        Livewire::actingAs($farmer)
            ->test(FarmerDashboard::class)
            ->assertSee('1', false);
    });

    it('refuses a client or a suspended farmer', function () {
        $client = User::factory()->client()->create();

        Livewire::actingAs($client)->test(FarmerDashboard::class)->assertForbidden();

        $suspended = User::factory()->farmer()->suspended()->create();

        Livewire::actingAs($suspended)->test(FarmerDashboard::class)->assertForbidden();
    });
});

describe('admin dashboard', function () {
    it('shows the platform-wide numbers to an administrator', function () {
        $admin = User::factory()->admin()->create();

        User::factory()->awaitingValidation()->create();

        Livewire::actingAs($admin)
            ->test(AdminDashboard::class)
            ->assertSee('1', false);
    });

    it('refuses everyone who is not an administrator', function () {
        $farmer = sellingFarmer();

        Livewire::actingAs($farmer)->test(AdminDashboard::class)->assertForbidden();
    });
});

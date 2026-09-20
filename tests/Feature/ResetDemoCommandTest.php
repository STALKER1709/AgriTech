<?php

declare(strict_types=1);

use App\Models\User;

/**
 * Phase 11: the demo reset puts the walkthrough data back.
 */
it('reseeds the demo data when forced', function () {
    // Wipe everything but keep the schema, then let the command rebuild it.
    User::query()->delete();

    $this->artisan('agritech:reset-demo', ['--force' => true])
        ->assertSuccessful();

    expect(User::query()->where('email', 'client@agritech.local')->exists())->toBeTrue();
    expect(User::query()->where('email', 'agriculteur@agritech.local')->exists())->toBeTrue();
});

it('does nothing when the operator declines', function () {
    $before = User::query()->count();

    $this->artisan('agritech:reset-demo')
        ->expectsConfirmation('Cette commande efface toutes les données locales et recharge la démonstration. Continuer ?', 'no')
        ->assertSuccessful();

    expect(User::query()->count())->toBe($before);
});

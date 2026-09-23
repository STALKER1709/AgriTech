<?php

declare(strict_types=1);

use App\Livewire\Account\Overview;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Support\Quantity;
use Database\Seeders\SettingSeeder;
use Livewire\Livewire;

/**
 * The account hub. It owns no data: every figure is a real count, and every
 * entry must lead to a page that exists.
 */
beforeEach(function () {
    $this->seed(SettingSeeder::class);
});

it('sends a signed-out visitor to the login screen', function () {
    $this->get(route('account.overview'))->assertRedirect(route('login'));
});

it('opens for each role', function (string $state) {
    $user = User::factory()->{$state}()->create();

    Livewire::actingAs($user)->test(Overview::class)->assertOk();
})->with(['client', 'farmer', 'admin']);

it('offers only entries that lead somewhere', function (string $state) {
    $user = User::factory()->{$state}()->create();

    /** @var Overview $overview */
    $overview = Livewire::actingAs($user)->test(Overview::class)->instance();

    $entries = [...$overview->activities(), ...$overview->preferences()];

    expect($entries)->not->toBeEmpty();

    foreach ($entries as $entry) {
        expect($entry['href'])->toStartWith(config('app.url'));
        expect($entry['title'])->toBeString()->not->toBeEmpty();

        // Une entrée qui mène à un 403 ou à un 404 serait un cul-de-sac : on
        // suit le lien pour de vrai. Une redirection reste une destination —
        // l'écran « Sécurité » demande d'abord de confirmer le mot de passe.
        $status = $this->actingAs($user)->get($entry['href'])->status();

        expect($status)->toBeIn([200, 302], $entry['href']);
    }
})->with(['client', 'farmer', 'admin']);

it('counts what the screens it links to would show', function () {
    $client = User::factory()->client()->create();

    $product = productOnSale();
    orderFor($client, [[$product, Quantity::fromInteger(2)]]);

    /** @var Overview $overview */
    $overview = Livewire::actingAs($client)->test(Overview::class)->instance();

    $orders = collect($overview->stats())->firstWhere('label', __('Commandes'));

    expect($orders['value'])->toBe(1);
});

it('names the running plan, and says so when there is none', function () {
    $client = User::factory()->client()->create();

    /** @var Overview $overview */
    $overview = Livewire::actingAs($client)->test(Overview::class)->instance();

    $pass = collect($overview->stats())->firstWhere('label', __('Pass'));

    expect($pass['value'])->toBe(__('Aucun'));

    $plan = SubscriptionPlan::factory()->create(['name' => 'Trimestriel']);
    Subscription::factory()->forClient($client)->active()->create(['plan_id' => $plan->id]);

    /** @var Overview $refreshed */
    $refreshed = Livewire::actingAs($client)->test(Overview::class)->instance();

    expect(collect($refreshed->stats())->firstWhere('label', __('Pass'))['value'])->toBe('Trimestriel');
});

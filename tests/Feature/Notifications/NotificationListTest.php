<?php

declare(strict_types=1);

use App\Livewire\Notifications\Index as NotificationList;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Notifications\FarmerApproved;
use App\Notifications\NewMessage;
use App\Notifications\OrderPaid;
use App\Support\NotificationPresenter;
use App\Support\Quantity;
use Database\Seeders\SettingSeeder;
use Illuminate\Support\Facades\Notification;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

/**
 * The notifications screen. It reads rows the services wrote; it never makes
 * one, and it never reads another account's.
 */
beforeEach(function () {
    $this->seed(SettingSeeder::class);

    $this->client = User::factory()->client()->create();
    $this->farmer = User::factory()->farmer()->create();
});

/**
 * `assertCount` reads a property; the visible rows are a method, so the test
 * asks the component itself.
 */
function visibleCount(Testable $component): int
{
    /** @var NotificationList $instance */
    $instance = $component->instance();

    return $instance->visible()->count();
}

it('shows only the signed-in account\'s notifications', function () {
    Notification::sendNow($this->client, new FarmerApproved, ['database']);
    Notification::sendNow($this->farmer, new FarmerApproved, ['database']);

    $component = Livewire::actingAs($this->client)->test(NotificationList::class)->assertOk();

    expect(visibleCount($component))->toBe(1);
});

it('sends a signed-out visitor to the login screen', function () {
    $this->get(route('notifications'))->assertRedirect(route('login'));
});

it('marks one notification as read', function () {
    Notification::sendNow($this->client, new FarmerApproved, ['database']);

    $id = (string) $this->client->notifications()->sole()->id;

    Livewire::actingAs($this->client)
        ->test(NotificationList::class)
        ->assertSet('unreadOnly', false)
        ->call('markAsRead', $id);

    expect($this->client->fresh()?->unreadNotifications()->count())->toBe(0);
});

it('marks every notification as read at once', function () {
    Notification::sendNow($this->client, new FarmerApproved, ['database']);
    Notification::sendNow($this->client, new FarmerApproved, ['database']);

    expect($this->client->unreadNotifications()->count())->toBe(2);

    Livewire::actingAs($this->client)
        ->test(NotificationList::class)
        ->call('markAllAsRead');

    expect($this->client->fresh()?->unreadNotifications()->count())->toBe(0);
});

it('refuses to open a notification belonging to somebody else', function () {
    Notification::sendNow($this->farmer, new FarmerApproved, ['database']);

    $id = (string) $this->farmer->notifications()->sole()->id;

    Livewire::actingAs($this->client)
        ->test(NotificationList::class)
        ->call('open', $id)
        ->assertNotFound();

    expect($this->farmer->fresh()?->unreadNotifications()->count())
        ->toBe(1, 'Une notification lue par quelqu\'un d\'autre resterait non lue.');
});

it('filters on the category the presenter gives each row', function () {
    $order = orderFor(
        $this->client,
        [[productOnSale(), Quantity::fromInteger(2)]],
    );

    Notification::sendNow($this->client, new OrderPaid($order), ['database']);
    Notification::sendNow($this->client, new FarmerApproved, ['database']);

    $component = Livewire::actingAs($this->client)->test(NotificationList::class);

    expect(visibleCount($component))->toBe(2);
    expect(visibleCount($component->set('category', 'orders')))->toBe(1);
    expect(visibleCount($component->set('category', 'account')))->toBe(1);
    expect(visibleCount($component->call('resetFilters')))->toBe(2);
});

it('keeps only the unread ones when asked', function () {
    Notification::sendNow($this->client, new FarmerApproved, ['database']);
    Notification::sendNow($this->client, new FarmerApproved, ['database']);

    $this->client->notifications()->first()?->markAsRead();

    $component = Livewire::actingAs($this->client)
        ->test(NotificationList::class)
        ->set('unreadOnly', true);

    expect(visibleCount($component))->toBe(1);
});

it('renders a row whose notification class has no dedicated presentation', function () {
    Notification::sendNow($this->client, new FarmerApproved, ['database']);

    // A class renamed or removed between two releases leaves its rows behind.
    // Losing the whole history to that would be worse than a plain line.
    $this->client->notifications()->update(['type' => 'App\\Notifications\\Gone']);

    Livewire::actingAs($this->client)
        ->test(NotificationList::class)
        ->assertOk()
        ->assertSee(__('Cette notification n\'a pas d\'affichage dédié.'));
});

it('links a message notification to the thread of the reader', function () {
    $conversation = Conversation::create([
        'client_id' => $this->client->id,
        'farmer_id' => $this->farmer->id,
        'last_message_at' => now(),
    ]);

    $message = Message::create([
        'conversation_id' => $conversation->id,
        'sender_id' => $this->farmer->id,
        'content' => 'La récolte est prête.',
    ]);

    Notification::sendNow($this->client, new NewMessage($message), ['database']);

    $row = $this->client->notifications()->sole();

    $presented = (new NotificationPresenter($this->client))->present($row);

    expect($presented['url'])->toBe(route('client.messages.show', ['conversation' => $conversation->id]));
    expect($presented['category'])->toBe('messages');
});

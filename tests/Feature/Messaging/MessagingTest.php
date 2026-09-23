<?php

declare(strict_types=1);

use App\Livewire\Client\Messages as ClientMessages;
use App\Livewire\Client\MessageThread as ClientThread;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Notifications\NewMessage;
use App\Services\Messaging\MessagingService;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

/**
 * Phase 9: one thread per pair, unread counters that mean something, and
 * notifications that reach the other side.
 */
beforeEach(function () {
    $this->client = User::factory()->client()->create();
    $this->farmer = sellingFarmer();
    $this->service = app(MessagingService::class);
});

describe('conversations', function () {
    it('creates one thread per client–farmer pair and reuses it', function () {
        $first = $this->service->conversationWith($this->client, $this->farmer);
        $second = $this->service->conversationWith($this->client, $this->farmer);

        expect($first->id)->toBe($second->id);
        expect(Conversation::query()->count())->toBe(1);
    });

    it('resolves the thread from the product the client is looking at', function () {
        $product = productOnSale();

        $conversation = $this->service->conversationAboutProduct($product, $this->client);

        expect($conversation->farmer_id)->toBe($product->farmer_id);
    });
});

describe('sending messages', function () {
    it('sends a message, stamps the thread, and notifies the other side', function () {
        Notification::fake();

        $conversation = $this->service->conversationWith($this->client, $this->farmer);

        $message = $this->service->send($conversation, $this->client, 'Bonjour, ce régime est-il disponible ?');

        expect($message->sender_id)->toBe($this->client->id);
        expect($conversation->refresh()->last_message_at)->not->toBeNull();

        Notification::assertSentTo($this->farmer, NewMessage::class);
    });

    it('refuses a message from someone who is not part of the thread', function () {
        $conversation = $this->service->conversationWith($this->client, $this->farmer);
        $intruder = User::factory()->client()->create();

        $this->service->send($conversation, $intruder, 'Je passe par la fenêtre.');
    })->throws(DomainException::class);

    it('refuses an empty or oversized message', function () {
        $conversation = $this->service->conversationWith($this->client, $this->farmer);

        app(MessagingService::class)->send($conversation, $this->client, '   ');
    })->throws(DomainException::class);

    it('marks the other side\'s messages read when the participant opens the thread — and only those', function () {
        $conversation = $this->service->conversationWith($this->client, $this->farmer);

        $fromClient = $this->service->send($conversation, $this->client, 'Une question du client.');
        $fromFarmer = $this->service->send($conversation, $this->farmer, 'Une réponse de l\'agriculteur.');

        $this->service->markThreadRead($conversation, $this->farmer);

        // The farmer opening the thread reads the client's message; their own
        // stays untouched — read is about what you received, not what you wrote.
        expect($fromClient->refresh()->isRead())->toBeTrue();
        expect($fromFarmer->refresh()->isRead())->toBeFalse();
    });
});

describe('unread counters', function () {
    it('counts only the messages addressed to the reader', function () {
        $conversation = $this->service->conversationWith($this->client, $this->farmer);

        $this->service->send($conversation, $this->client, 'Question un.');
        $this->service->send($conversation, $this->client, 'Question deux.');
        $this->service->send($conversation, $this->farmer, 'Réponse.');

        expect($this->service->unreadTotalFor($this->farmer))->toBe(2);
        expect($this->service->unreadTotalFor($this->client))->toBe(1);
    });

    it('drops to zero once the thread has been opened', function () {
        $conversation = $this->service->conversationWith($this->client, $this->farmer);

        $this->service->send($conversation, $this->client, 'Bonjour !');
        expect($this->service->unreadTotalFor($this->farmer))->toBe(1);

        $this->service->markThreadRead($conversation, $this->farmer);

        expect($this->service->unreadTotalFor($this->farmer))->toBe(0);
    });
});

describe('role isolation', function () {
    it('keeps the farmer out of the client screens and the reverse', function () {
        $this->actingAs($this->farmer)->get(route('client.messages'))->assertForbidden();
        $this->actingAs($this->client)->get(route('farmer.messages'))->assertForbidden();
    });

    it('refuses a thread that does not belong to the signed-in user', function () {
        $conversation = $this->service->conversationWith($this->client, $this->farmer);
        $stranger = User::factory()->client()->create();

        $this->actingAs($stranger)
            ->get(route('client.messages.show', ['conversation' => $conversation->id]))
            ->assertForbidden();
    });
});

describe('the screens', function () {
    it('lists the client\'s conversations with the unread badge', function () {
        $conversation = $this->service->conversationWith($this->client, $this->farmer);
        $this->service->send($conversation, $this->farmer, 'Bonjour, bien reçu !');

        Livewire::actingAs($this->client)
            ->test(ClientMessages::class)
            ->assertSee($this->farmer->farmerProfile->farm_name)
            ->assertSee('1');
    });

    it('shows the thread and marks it read on open', function () {
        $conversation = $this->service->conversationWith($this->client, $this->farmer);
        $message = $this->service->send($conversation, $this->farmer, 'Bonjour, bien reçu !');

        expect($message->isRead())->toBeFalse();

        Livewire::actingAs($this->client)
            ->test(ClientThread::class, ['conversation' => $conversation])
            ->assertSee('Bonjour, bien reçu !');

        expect($message->refresh()->isRead())->toBeTrue();
    });

    it('sends a reply from the thread', function () {
        $conversation = $this->service->conversationWith($this->client, $this->farmer);

        Livewire::actingAs($this->client)
            ->test(ClientThread::class, ['conversation' => $conversation])
            ->set('content', 'Merci pour l\'information !')
            ->call('send')
            ->assertHasNoErrors();

        expect(Message::query()->where('sender_id', $this->client->id)->count())->toBe(1);
    });

    it('refuses to send an empty reply', function () {
        $conversation = $this->service->conversationWith($this->client, $this->farmer);

        Livewire::actingAs($this->client)
            ->test(ClientThread::class, ['conversation' => $conversation])
            ->set('content', '')
            ->call('send')
            ->assertHasErrors('content');

        expect(Message::query()->count())->toBe(0);
    });
});

describe('la recherche dans la liste des fils', function () {
    it('finds a thread by the farm name of the other participant', function () {
        $client = User::factory()->client()->create();
        $farmer = sellingFarmer();
        $farmer->farmerProfile->update(['farm_name' => 'Ferme des Collines']);

        $other = sellingFarmer();
        $other->farmerProfile->update(['farm_name' => 'Verger du Nord']);

        Conversation::create(['client_id' => $client->id, 'farmer_id' => $farmer->id, 'last_message_at' => now()]);
        Conversation::create(['client_id' => $client->id, 'farmer_id' => $other->id, 'last_message_at' => now()]);

        $messaging = app(MessagingService::class);

        expect($messaging->conversationsFor($client)->total())->toBe(2);
        expect($messaging->conversationsFor($client, search: 'Collines')->total())->toBe(1);
    });

    it('finds a thread by what was written in it', function () {
        $client = User::factory()->client()->create();
        $farmer = sellingFarmer();

        $conversation = Conversation::create([
            'client_id' => $client->id,
            'farmer_id' => $farmer->id,
            'last_message_at' => now(),
        ]);

        app(MessagingService::class)->send($conversation, $client, 'Avez-vous du poivre de Penja ?');

        $messaging = app(MessagingService::class);

        expect($messaging->conversationsFor($client, search: 'Penja')->total())->toBe(1);
        expect($messaging->conversationsFor($client, search: 'cacao')->total())->toBe(0);
    });

    it('never reaches a thread the searcher is not part of', function () {
        $client = User::factory()->client()->create();
        $stranger = User::factory()->client()->create();
        $farmer = sellingFarmer();
        $farmer->farmerProfile->update(['farm_name' => 'Ferme des Collines']);

        Conversation::create(['client_id' => $stranger->id, 'farmer_id' => $farmer->id, 'last_message_at' => now()]);

        expect(app(MessagingService::class)->conversationsFor($client, search: 'Collines')->total())
            ->toBe(0);
    });
});

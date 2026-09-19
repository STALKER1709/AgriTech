<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Conversation;
use App\Models\FarmerProfile;
use App\Models\Message;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Privilege;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\SubOrder;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Training;
use App\Models\TrainingContent;
use App\Models\TrainingPurchase;
use App\Models\User;

it('links a farmer to their profile both ways', function () {
    $farmer = User::factory()->farmer()->create();
    $profile = FarmerProfile::factory()->create(['user_id' => $farmer->id]);

    expect($farmer->refresh()->farmerProfile->is($profile))->toBeTrue();
    expect($profile->user->is($farmer))->toBeTrue();
});

it('links admins and privileges both ways', function () {
    $admin = User::factory()->admin()->create();
    $privilege = Privilege::factory()->withCode(Privilege::APPROVE_FARMERS)->create();

    $admin->privileges()->attach($privilege);

    expect($admin->refresh()->privileges->pluck('code')->all())->toBe([Privilege::APPROVE_FARMERS]);
    expect($privilege->refresh()->users->pluck('id')->all())->toBe([$admin->id]);
});

it('links a product to its farmer, category and images', function () {
    $farmer = User::factory()->farmer()->create();
    $category = Category::factory()->create();
    $product = Product::factory()->create([
        'farmer_id' => $farmer->id,
        'category_id' => $category->id,
    ]);
    $image = ProductImage::factory()->create(['product_id' => $product->id]);

    expect($product->farmer->is($farmer))->toBeTrue();
    expect($product->category->is($category))->toBeTrue();
    expect($product->images->pluck('id')->all())->toBe([$image->id]);
    expect($farmer->products->pluck('id')->all())->toBe([$product->id]);
    expect($category->products->pluck('id')->all())->toBe([$product->id]);
});

it('orders product images by position', function () {
    $product = Product::factory()->create();

    $third = ProductImage::factory()->create(['product_id' => $product->id, 'position' => 3]);
    $first = ProductImage::factory()->create(['product_id' => $product->id, 'position' => 1]);
    $second = ProductImage::factory()->create(['product_id' => $product->id, 'position' => 2]);

    expect($product->images->pluck('id')->all())->toBe([$first->id, $second->id, $third->id]);
});

it('links a training to its farmer, contents and purchases', function () {
    $farmer = User::factory()->farmer()->create();
    $client = User::factory()->client()->create();
    $training = Training::factory()->forFarmer($farmer)->create();
    $content = TrainingContent::factory()->create(['training_id' => $training->id]);
    $purchase = TrainingPurchase::factory()->create([
        'client_id' => $client->id,
        'training_id' => $training->id,
    ]);

    expect($training->farmer->is($farmer))->toBeTrue();
    expect($training->contents->pluck('id')->all())->toBe([$content->id]);
    expect($training->purchases->pluck('id')->all())->toBe([$purchase->id]);
    expect($client->trainingPurchases->pluck('id')->all())->toBe([$purchase->id]);
    expect($purchase->training->is($training))->toBeTrue();
});

it('links an order to its client, sub-orders and items', function () {
    $client = User::factory()->client()->create();
    $farmer = User::factory()->farmer()->create();

    $order = Order::factory()->forClient($client)->create();
    $subOrder = SubOrder::factory()->forFarmer($farmer)->create(['order_id' => $order->id]);
    $item = OrderItem::factory()->create(['sub_order_id' => $subOrder->id]);

    expect($order->client->is($client))->toBeTrue();
    expect($order->subOrders->pluck('id')->all())->toBe([$subOrder->id]);
    expect($subOrder->order->is($order))->toBeTrue();
    expect($subOrder->farmer->is($farmer))->toBeTrue();
    expect($subOrder->items->pluck('id')->all())->toBe([$item->id]);
    expect($item->subOrder->is($subOrder))->toBeTrue();
    expect($client->orders->pluck('id')->all())->toBe([$order->id]);
    expect($farmer->subOrders->pluck('id')->all())->toBe([$subOrder->id]);
});

it('reaches the order items through the sub-orders', function () {
    $order = Order::factory()->create();
    $first = SubOrder::factory()->create(['order_id' => $order->id]);
    $second = SubOrder::factory()->create(['order_id' => $order->id]);

    OrderItem::factory()->count(2)->create(['sub_order_id' => $first->id]);
    OrderItem::factory()->create(['sub_order_id' => $second->id]);

    expect($order->items()->count())->toBe(3);
});

it('links a subscription to its client and plan', function () {
    $client = User::factory()->client()->create();
    $plan = SubscriptionPlan::factory()->create();
    $subscription = Subscription::factory()->create([
        'client_id' => $client->id,
        'plan_id' => $plan->id,
    ]);

    expect($subscription->client->is($client))->toBeTrue();
    expect($subscription->plan->is($plan))->toBeTrue();
    expect($plan->subscriptions->pluck('id')->all())->toBe([$subscription->id]);
    expect($client->subscriptions->pluck('id')->all())->toBe([$subscription->id]);
});

it('attaches a payment to whatever it pays for', function () {
    $client = User::factory()->client()->create();
    $order = Order::factory()->forClient($client)->create();

    $payment = Payment::factory()->for_($order)->create(['user_id' => $client->id]);

    expect($payment->payable)->toBeInstanceOf(Order::class);
    expect($payment->payable->is($order))->toBeTrue();
    expect($order->payments->pluck('id')->all())->toBe([$payment->id]);
    expect($client->payments->pluck('id')->all())->toBe([$payment->id]);
});

it('links a conversation to both participants and its messages', function () {
    $client = User::factory()->client()->create();
    $farmer = User::factory()->farmer()->create();
    $conversation = Conversation::factory()->between($client, $farmer)->create();
    $message = Message::factory()->from($client)->create(['conversation_id' => $conversation->id]);

    expect($conversation->client->is($client))->toBeTrue();
    expect($conversation->farmer->is($farmer))->toBeTrue();
    expect($conversation->messages->pluck('id')->all())->toBe([$message->id]);
    expect($message->conversation->is($conversation))->toBeTrue();
    expect($message->sender->is($client))->toBeTrue();
    expect($client->clientConversations->pluck('id')->all())->toBe([$conversation->id]);
    expect($farmer->farmerConversations->pluck('id')->all())->toBe([$conversation->id]);
});

it('counts unread messages for a participant, ignoring their own', function () {
    $client = User::factory()->client()->create();
    $farmer = User::factory()->farmer()->create();
    $conversation = Conversation::factory()->between($client, $farmer)->create();

    Message::factory()->count(3)->from($farmer)->create(['conversation_id' => $conversation->id]);
    Message::factory()->from($farmer)->read()->create(['conversation_id' => $conversation->id]);
    Message::factory()->count(2)->from($client)->create(['conversation_id' => $conversation->id]);

    expect($conversation->unreadCountFor($client))->toBe(3);
    expect($conversation->unreadCountFor($farmer))->toBe(2);
});

it('assembles the display name from the two name columns', function () {
    $user = User::factory()->create(['first_name' => 'Clarisse', 'last_name' => 'Etoundi']);

    expect($user->name)->toBe('Clarisse Etoundi');
    expect($user->initials())->toBe('CE');
});

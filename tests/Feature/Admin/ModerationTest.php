<?php

declare(strict_types=1);

use App\Enums\PublicationStatus;
use App\Livewire\Admin\Categories as CategoriesScreen;
use App\Livewire\Admin\Moderation;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Privilege;
use App\Models\Product;
use App\Models\Training;
use App\Notifications\PublicationApproved;
use App\Notifications\PublicationRejected;
use App\Services\Admin\AuditLogger;
use Database\Seeders\PrivilegeSeeder;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
    $this->seed(PrivilegeSeeder::class);
});

describe('RG07 on moderation', function () {
    it('is closed to an administrator without the privilege', function () {
        Livewire::actingAs(adminWith(Privilege::SUSPEND_USERS))
            ->test(Moderation::class)
            ->assertForbidden();
    });

    it('opens for an administrator who holds it', function () {
        Livewire::actingAs(adminWith(Privilege::MODERATE_PUBLICATIONS))
            ->test(Moderation::class)
            ->assertOk();
    });
});

describe('approving', function () {
    it('publishes the product and tells the farmer', function () {
        $product = Product::factory()->inReview()->create();

        Livewire::actingAs(adminWith(Privilege::MODERATE_PUBLICATIONS))
            ->test(Moderation::class)
            ->call('approve', 'product', $product->id);

        expect($product->refresh()->status)->toBe(PublicationStatus::Published);

        Notification::assertSentTo($product->farmer, PublicationApproved::class);
    });

    it('publishes a training the same way', function () {
        $training = Training::factory()->inReview()->create();

        Livewire::actingAs(adminWith(Privilege::MODERATE_PUBLICATIONS))
            ->test(Moderation::class)
            ->call('approve', 'training', $training->id);

        expect($training->refresh()->status)->toBe(PublicationStatus::Published);
    });

    it('records the decision, per business rule RG11', function () {
        $product = Product::factory()->inReview()->create();
        $admin = adminWith(Privilege::MODERATE_PUBLICATIONS);

        Livewire::actingAs($admin)->test(Moderation::class)->call('approve', 'product', $product->id);

        $entry = AuditLog::query()->latest('id')->firstOrFail();

        expect($entry->action)->toBe(AuditLogger::PUBLICATION_APPROVED);
        expect($entry->actor_id)->toBe($admin->id);
        expect($entry->before['status'])->toBe('in_review');
        expect($entry->after['status'])->toBe('published');
    });

    it('refuses to act on something not under review', function () {
        $product = Product::factory()->draft()->create();

        Livewire::actingAs(adminWith(Privilege::MODERATE_PUBLICATIONS))
            ->test(Moderation::class)
            ->call('approve', 'product', $product->id)
            ->assertForbidden();
    });
});

describe('rejecting', function () {
    it('records the reason on the product and sends it to the farmer', function () {
        $product = Product::factory()->inReview()->create();

        Livewire::actingAs(adminWith(Privilege::MODERATE_PUBLICATIONS))
            ->test(Moderation::class)
            ->call('startRejection', 'product', $product->id)
            ->set('reason', 'La photo ne correspond pas au produit décrit.')
            ->call('reject')
            ->assertHasNoErrors();

        $product->refresh();

        expect($product->status)->toBe(PublicationStatus::Rejected);
        expect($product->rejection_reason)->toBe('La photo ne correspond pas au produit décrit.');

        Notification::assertSentTo(
            $product->farmer,
            PublicationRejected::class,
            fn (PublicationRejected $notification): bool => str_contains($notification->reason, 'photo'),
        );
    });

    it('demands a reason worth reading', function () {
        $product = Product::factory()->inReview()->create();

        Livewire::actingAs(adminWith(Privilege::MODERATE_PUBLICATIONS))
            ->test(Moderation::class)
            ->call('startRejection', 'product', $product->id)
            ->set('reason', 'non')
            ->call('reject')
            ->assertHasErrors('reason');

        expect($product->refresh()->status)->toBe(PublicationStatus::InReview);
    });
});

describe('categories', function () {
    it('is closed without the privilege', function () {
        Livewire::actingAs(adminWith(Privilege::MODERATE_PUBLICATIONS))
            ->test(CategoriesScreen::class)
            ->assertForbidden();
    });

    it('creates a category with a slug', function () {
        Livewire::actingAs(adminWith(Privilege::MANAGE_CATEGORIES))
            ->test(CategoriesScreen::class)
            ->set('newName', 'Plantes aromatiques')
            ->call('create')
            ->assertHasNoErrors();

        $category = Category::query()->where('name', 'Plantes aromatiques')->sole();

        expect($category->slug)->toBe('plantes-aromatiques');
        expect(AuditLog::query()->latest('id')->first()->action)->toBe(AuditLogger::CATEGORY_CREATED);
    });

    it('refuses a duplicate name', function () {
        Category::factory()->create(['name' => 'Légumes']);

        Livewire::actingAs(adminWith(Privilege::MANAGE_CATEGORIES))
            ->test(CategoriesScreen::class)
            ->set('newName', 'Légumes')
            ->call('create')
            ->assertHasErrors('newName');
    });

    it('keeps the slug when renaming, so catalogue links survive', function () {
        $category = Category::factory()->create(['name' => 'Legumes', 'slug' => 'legumes']);

        Livewire::actingAs(adminWith(Privilege::MANAGE_CATEGORIES))
            ->test(CategoriesScreen::class)
            ->call('edit', $category->id)
            ->set('editedName', 'Légumes frais')
            ->call('rename');

        $category->refresh();

        expect($category->name)->toBe('Légumes frais');
        expect($category->slug)->toBe('legumes');
    });

    it('deletes an empty category', function () {
        $category = Category::factory()->create();

        Livewire::actingAs(adminWith(Privilege::MANAGE_CATEGORIES))
            ->test(CategoriesScreen::class)
            ->call('delete', $category->id);

        expect(Category::query()->find($category->id))->toBeNull();
    });

    it('never deletes a category that still holds products', function () {
        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id]);

        Livewire::actingAs(adminWith(Privilege::MANAGE_CATEGORIES))
            ->test(CategoriesScreen::class)
            ->call('delete', $category->id)
            ->assertForbidden();

        expect(Category::query()->find($category->id))->not->toBeNull();
    });
});

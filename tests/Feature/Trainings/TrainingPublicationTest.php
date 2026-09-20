<?php

declare(strict_types=1);

use App\Enums\PublicationStatus;
use App\Livewire\Farmer\TrainingForm;
use App\Livewire\Farmer\TrainingList;
use App\Models\Setting;
use App\Models\Training;
use App\Models\User;
use App\Services\Catalog\PublicationService;
use App\Services\Trainings\TrainingService;
use Database\Seeders\SettingSeeder;
use Livewire\Livewire;

/**
 * Business rules RG01 (an inactive farmer publishes nothing) and RG09 (a
 * publication reaches the catalogue through moderation) applied to trainings.
 */
beforeEach(function () {
    $this->seed(SettingSeeder::class);

    $this->farmer = sellingFarmer();
});

describe('RG01 — publishing needs an active account', function () {
    it('lets an active farmer create a training', function () {
        Livewire::actingAs($this->farmer)
            ->test(TrainingForm::class)
            ->set('title', 'Faire son propre compost')
            ->set('description', 'Une formation complète sur le compostage fermier, du tas à l\'épandage.')
            ->set('price', '5000')
            ->set('format', 'video')
            ->call('save')
            ->assertHasNoErrors();

        $training = Training::query()->sole();

        expect($training->status)->toBe(PublicationStatus::Draft);
        expect($training->farmer_id)->toBe($this->farmer->id);
        expect($training->slug)->toBe('faire-son-propre-compost');
    });

    it('refuses the form to a suspended farmer', function () {
        $suspended = User::factory()->farmer()->suspended()->create();

        Livewire::actingAs($suspended)->test(TrainingForm::class)->assertForbidden();
    });

    it('refuses the submission of a suspended farmer, not only the screen', function () {
        $suspended = User::factory()->farmer()->suspended()->create();
        $training = Training::factory()->draft()->forFarmer($suspended)->create();

        app(PublicationService::class)->submit($training, $suspended);
    })->throws(DomainException::class);
});

describe('RG09 — moderation before publication', function () {
    it('sends a submitted training to review', function () {
        $training = Training::factory()->draft()->forFarmer($this->farmer)->create();

        Livewire::actingAs($this->farmer)
            ->test(TrainingList::class)
            ->call('submit', $training->id);

        expect($training->refresh()->status)->toBe(PublicationStatus::InReview);
    });

    it('publishes straight away when prior moderation is switched off', function () {
        Setting::query()
            ->where('key', Setting::PRIOR_MODERATION_ENABLED)
            ->update(['value' => '0']);

        $training = Training::factory()->draft()->forFarmer($this->farmer)->create();

        Livewire::actingAs($this->farmer)
            ->test(TrainingList::class)
            ->call('submit', $training->id);

        expect($training->refresh()->status)->toBe(PublicationStatus::Published);
    });

    it('lets a rejected training be corrected and submitted again', function () {
        $training = Training::factory()->rejected()->forFarmer($this->farmer)->create();

        Livewire::actingAs($this->farmer)
            ->test(TrainingList::class)
            ->call('submit', $training->id);

        expect($training->refresh()->status)->toBe(PublicationStatus::InReview);
        expect($training->rejection_reason)->toBeNull();
    });
});

describe('ownership and slugs', function () {
    it('never lets a farmer edit another farmer\'s training', function () {
        $someoneElse = Training::factory()->draft()->create();

        Livewire::actingAs($this->farmer)
            ->test(TrainingForm::class, ['training' => $someoneElse])
            ->assertForbidden();
    });

    it('keeps the slug when the title has not changed', function () {
        $training = Training::factory()->create(['title' => 'Irriguer sans gaspiller', 'slug' => 'irriguer-sans-gaspiller']);

        app(TrainingService::class)->update($training, [
            'title' => 'Irriguer sans gaspiller',
            'description' => $training->description,
            'price' => 6000,
            'format' => $training->format->value,
            'included_in_subscription' => true,
        ]);

        expect($training->refresh()->slug)->toBe('irriguer-sans-gaspiller');
    });

    it('refuses to archive a training that is not the farmer\'s', function () {
        $someoneElse = Training::factory()->create();

        expect($this->farmer->can('archive', $someoneElse))->toBeFalse();
    });
});

describe('public listing', function () {
    it('shows only published trainings of active farmers', function () {
        Training::factory()->published()->create(['title' => 'Formation visible']);
        Training::factory()->draft()->create(['title' => 'Formation cachee']);

        $suspended = sellingFarmer();
        $suspended->suspend();
        Training::factory()->published()->forFarmer($suspended)->create(['title' => 'Formation suspendue']);

        $this->get(route('trainings.index'))
            ->assertOk()
            ->assertSee('Formation visible')
            ->assertDontSee('Formation cachee')
            ->assertDontSee('Formation suspendue');
    });

    it('answers 404 for a training that is not public', function () {
        $training = Training::factory()->draft()->create();

        $this->get(route('trainings.show', ['training' => $training->slug]))
            ->assertNotFound();
    });
});

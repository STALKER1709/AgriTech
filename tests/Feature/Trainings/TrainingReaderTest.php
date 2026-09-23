<?php

declare(strict_types=1);

use App\Livewire\Trainings\Reader;
use App\Models\Subscription;
use App\Models\Training;
use App\Models\TrainingContent;
use App\Models\TrainingPurchase;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Livewire;

/**
 * The reader screen, which shows a training module by module.
 *
 * Business rule RG05 again: the screen runs the same entitlement check as the
 * content controller, one step earlier, so that a client without the right
 * never reaches a page listing what they cannot open.
 */
beforeEach(function () {
    $this->client = User::factory()->client()->create();
});

/**
 * @return array{0: Training, 1: Collection<int, TrainingContent>}
 */
function readableTraining(array $overrides = [], int $modules = 3): array
{
    $training = trainingOnSale($overrides);

    $contents = collect(range(1, $modules))->map(fn (int $position) => TrainingContent::factory()->pdf()->create([
        'training_id' => $training->id,
        'title' => 'Module '.$position,
        'position' => $position,
    ]));

    return [$training, $contents];
}

describe('who may open the reader', function () {
    it('opens for a client who bought the training', function () {
        [$training, $contents] = readableTraining();

        TrainingPurchase::factory()->create([
            'client_id' => $this->client->id,
            'training_id' => $training->id,
        ]);

        Livewire::actingAs($this->client)
            ->test(Reader::class, ['training' => $training])
            ->assertOk()
            ->assertSee($contents->first()->title);
    });

    it('opens for a client whose active subscription includes it', function () {
        [$training] = readableTraining(['included_in_subscription' => true]);

        Subscription::factory()->forClient($this->client)->active()->create();

        Livewire::actingAs($this->client)
            ->test(Reader::class, ['training' => $training])
            ->assertOk();
    });

    it('refuses a client who owns neither a purchase nor a running Pass', function () {
        [$training] = readableTraining(['included_in_subscription' => true]);

        Subscription::factory()->forClient($this->client)->lapsed()->create();

        Livewire::actingAs($this->client)
            ->test(Reader::class, ['training' => $training])
            ->assertForbidden();
    });

    it('sends a signed-out visitor to the login screen', function () {
        [$training] = readableTraining();

        $this->get(route('trainings.read', ['training' => $training->slug]))
            ->assertRedirect(route('login'));
    });
});

describe('choosing a module', function () {
    beforeEach(function () {
        [$this->training, $this->contents] = readableTraining();

        TrainingPurchase::factory()->create([
            'client_id' => $this->client->id,
            'training_id' => $this->training->id,
        ]);
    });

    it('starts on the first module', function () {
        Livewire::actingAs($this->client)
            ->test(Reader::class, ['training' => $this->training])
            ->assertSet('content.id', $this->contents->first()->id);
    });

    it('moves to the one asked for', function () {
        Livewire::actingAs($this->client)
            ->test(Reader::class, ['training' => $this->training])
            ->call('open', $this->contents->last()->id)
            ->assertSet('content.id', $this->contents->last()->id);
    });

    it('refuses a module belonging to another training', function () {
        $other = TrainingContent::factory()->pdf()->create();

        Livewire::actingAs($this->client)
            ->test(Reader::class, ['training' => $this->training])
            ->call('open', $other->id)
            ->assertNotFound();
    });

    it('refuses to mount on another training\'s module', function () {
        $other = TrainingContent::factory()->pdf()->create();

        Livewire::actingAs($this->client)
            ->test(Reader::class, ['training' => $this->training, 'content' => $other])
            ->assertNotFound();
    });

    it('answers 404 when the training has no module at all', function () {
        $empty = trainingOnSale();

        TrainingPurchase::factory()->create([
            'client_id' => $this->client->id,
            'training_id' => $empty->id,
        ]);

        Livewire::actingAs($this->client)
            ->test(Reader::class, ['training' => $empty])
            ->assertNotFound();
    });
});

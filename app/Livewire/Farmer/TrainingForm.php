<?php

declare(strict_types=1);

namespace App\Livewire\Farmer;

use App\Enums\TrainingContentType;
use App\Enums\TrainingFormat;
use App\Models\Training;
use App\Models\TrainingContent;
use App\Models\User;
use App\Services\Trainings\TrainingContentStore;
use App\Services\Trainings\TrainingService;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Title('Fiche formation')]
class TrainingForm extends Component
{
    use WithFileUploads;

    public ?Training $training = null;

    public string $title = '';

    public string $description = '';

    public string $price = '';

    public string $format = TrainingFormat::Video->value;

    public bool $included_in_subscription = false;

    public string $content_title = '';

    public string $content_type = TrainingContentType::Video->value;

    /**
     * @var array<int, TemporaryUploadedFile>
     */
    public array $uploads = [];

    public function mount(?Training $training = null): void
    {
        if ($training?->exists) {
            $this->authorize('update', $training);

            $this->training = $training;
            $this->title = $training->title;
            $this->description = $training->description;
            $this->price = (string) $training->price->amount;
            $this->format = $training->format->value;
            $this->included_in_subscription = $training->included_in_subscription;

            return;
        }

        $this->authorize('create', Training::class);
    }

    /**
     * @return Collection<int, TrainingContent>
     */
    public function contents(): Collection
    {
        return $this->training?->contents()->orderBy('position')->get() ?? collect();
    }

    /**
     * @return array<int, TrainingFormat>
     */
    public function formats(): array
    {
        return TrainingFormat::cases();
    }

    /**
     * @return array<int, TrainingContentType>
     */
    public function contentTypes(): array
    {
        return TrainingContentType::cases();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        $contents = config('trainings.contents');

        return [
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'description' => ['required', 'string', 'min:20', 'max:5000'],
            // Business rule RG10: whole francs, like every amount.
            'price' => ['required', 'regex:/^\d+$/', 'min:1'],
            'format' => ['required', 'string', 'in:'.implode(',', array_column(TrainingFormat::cases(), 'value'))],
            'included_in_subscription' => ['boolean'],
            'content_title' => ['required_with:uploads', 'string', 'min:2', 'max:255'],
            'content_type' => ['required_with:uploads', 'string', 'in:'.implode(',', array_column(TrainingContentType::cases(), 'value'))],
            'uploads' => ['array', 'max:'.$contents['max_per_training']],
            // `file` checks the real content, not the extension in the name.
            'uploads.*' => [
                'file',
                'mimes:'.implode(',', $contents['mimes']),
                'max:'.$contents['max_kilobytes'],
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'title' => __('titre de la formation'),
            'description' => __('description'),
            'price' => __('prix'),
            'format' => __('format'),
            'included_in_subscription' => __('inclusion dans l\'abonnement'),
            'content_title' => __('titre du contenu'),
            'content_type' => __('type de contenu'),
            'uploads' => __('fichiers'),
            'uploads.*' => __('fichier'),
        ];
    }

    public function save(TrainingService $trainings): void
    {
        $validated = $this->validate();

        $attributes = [
            'title' => $validated['title'],
            'description' => $validated['description'],
            'price' => (int) $validated['price'],
            'format' => $validated['format'],
            'included_in_subscription' => (bool) $this->included_in_subscription,
        ];

        if ($this->training?->exists) {
            $this->authorize('update', $this->training);
            $trainings->update($this->training, $attributes);
        } else {
            $this->authorize('create', Training::class);
            $trainings->create($this->farmer(), $attributes);
        }

        Flux::toast(variant: 'success', text: __('Formation enregistrée.'));

        $this->redirectRoute('farmer.trainings', navigate: true);
    }

    public function addContent(TrainingContentStore $store): void
    {
        abort_unless($this->training?->exists, 404);

        $this->authorize('update', $this->training);

        $validated = $this->validate([
            'content_title' => ['required', 'string', 'min:2', 'max:255'],
            'content_type' => ['required', 'string', 'in:'.implode(',', array_column(TrainingContentType::cases(), 'value'))],
            'uploads' => ['required', 'array', 'min:1', 'max:1'],
            'uploads.0' => [
                'required',
                'file',
                'mimes:'.implode(',', config('trainings.contents.mimes')),
                'max:'.config('trainings.contents.max_kilobytes'),
            ],
        ], [], [
            'uploads' => __('fichiers'),
            'uploads.0' => __('fichier'),
        ]);

        $store->add(
            $this->training,
            $validated['content_title'],
            TrainingContentType::from($validated['content_type']),
            $this->uploads[0],
        );

        $this->uploads = [];
        $this->content_title = '';

        Flux::toast(variant: 'success', text: __('Contenu ajouté.'));
    }

    public function removeContent(int $contentId, TrainingContentStore $store): void
    {
        $content = TrainingContent::query()->findOrFail($contentId);

        abort_unless($this->training !== null && $content->training_id === $this->training->id, 403);

        $this->authorize('update', $this->training);

        $store->remove($content);
    }

    private function farmer(): User
    {
        $farmer = Auth::user();

        abort_unless($farmer instanceof User, 403);

        return $farmer;
    }

    public function render(): mixed
    {
        return view('livewire.farmer.training-form');
    }
}

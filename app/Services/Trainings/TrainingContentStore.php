<?php

declare(strict_types=1);

namespace App\Services\Trainings;

use App\Enums\TrainingContentType;
use App\Models\Training;
use App\Models\TrainingContent;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores and removes the files that make up a training.
 *
 * Same shape as ProductImageStore, and business rule RG05 is the reason: the
 * files live on the private disk and are served by a controller that checks
 * the entitlement, never by a direct URL. The uploaded name is thrown away and
 * replaced by a ULID, exactly like the product images.
 *
 * Videos and PDFs are each capped far below what a genuine production platform
 * would allow; the values live in config/trainings.php because they belong to
 * the product, not to the developer.
 */
final class TrainingContentStore
{
    public function add(Training $training, string $title, TrainingContentType $type, UploadedFile $file): TrainingContent
    {
        $extension = Str::lower($file->extension() ?: $file->getClientOriginalExtension());
        $name = Str::ulid()->toString().'.'.$extension;

        $path = $file->storeAs($this->directory($training), $name, ['disk' => $this->disk()]);

        return TrainingContent::create([
            'training_id' => $training->id,
            'title' => $title,
            'type' => $type,
            'path' => (string) $path,
            'position' => $this->nextPosition($training),
        ]);
    }

    public function remove(TrainingContent $content): void
    {
        Storage::disk($this->disk())->delete($content->path);

        $content->delete();
    }

    /**
     * Remove every file a training owns, for when the training itself goes.
     */
    public function removeAll(Training $training): void
    {
        $training->loadMissing('contents');

        foreach ($training->contents as $content) {
            $this->remove($content);
        }
    }

    public function disk(): string
    {
        return (string) config('trainings.contents.disk', 'local');
    }

    private function directory(Training $training): string
    {
        return trim((string) config('trainings.contents.directory', 'trainings'), '/').'/'.$training->id;
    }

    private function nextPosition(Training $training): int
    {
        return (int) $training->contents()->max('position') + 1;
    }
}

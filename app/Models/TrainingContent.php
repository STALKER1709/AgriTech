<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TrainingContentType;
use Database\Factories\TrainingContentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single file making up a training: one video or one PDF.
 *
 * The path points at the private disk. Business rule RG05 requires it to be
 * served by a controller that checks entitlement, so the path is hidden from
 * serialisation and must never reach a view.
 *
 * @property int $id
 * @property int $training_id
 * @property string $title
 * @property TrainingContentType $type
 * @property string $path
 * @property int $position
 */
#[Fillable(['training_id', 'title', 'type', 'path', 'position'])]
#[Hidden(['path'])]
class TrainingContent extends Model
{
    /** @use HasFactory<TrainingContentFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TrainingContentType::class,
            'position' => 'integer',
        ];
    }

    /** @return BelongsTo<Training, $this> */
    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }
}

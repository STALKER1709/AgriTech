<?php

declare(strict_types=1);

namespace App\Http\Controllers\Catalog;

use App\Enums\PublicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Training;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves a training's illustrated cover from the private disk.
 *
 * Covers are keyed by training slug and drawn by the demo seeder; a training
 * published without one simply never reaches this route, because the views
 * check existence before building the URL. Like product images, covers are
 * public information once the training is published — routing them through a
 * controller keeps the app independent of storage:link, the one piece of the
 * Windows setup that could not be verified.
 */
final class TrainingCoverController extends Controller
{
    public function __invoke(Training $training): Response
    {
        // A cover belongs to public marketing material: refuse it for anything
        // that is not on the public shelf (a suspended farmer's trainings,
        // drafts, moderated items), exactly like the training page itself.
        abort_unless($training->status === PublicationStatus::Published, 404);
        abort_unless($training->farmer->isActive(), 404);

        $disk = Storage::disk((string) config('catalog.images.disk', 'local'));
        $path = 'training-covers/'.$training->slug.'.png';

        abort_unless($disk->exists($path), 404);

        return $disk->response(
            $path,
            headers: [
                // The filename derives from the immutable slug, so a long
                // cache is safe and keeps the shelf from re-fetching.
                'Cache-Control' => 'public, max-age=31536000, immutable',
            ],
        );
    }
}

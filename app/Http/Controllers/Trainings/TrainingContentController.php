<?php

declare(strict_types=1);

namespace App\Http\Controllers\Trainings;

use App\Http\Controllers\Controller;
use App\Models\TrainingContent;
use App\Models\User;
use App\Services\Trainings\TrainingAccessService;
use App\Services\Trainings\TrainingContentStore;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves a training file to a client who is entitled to it — and to nobody
 * else.
 *
 * Business rule RG05: the content of a paid training is never reachable by a
 * direct URL. The file lives on the private disk, and the path on the model is
 * hidden from serialisation; the only way in is this controller, which checks
 * the entitlement before a single byte is sent.
 */
final class TrainingContentController extends Controller
{
    public function __invoke(
        TrainingContent $content,
        TrainingAccessService $access,
        TrainingContentStore $store,
    ): StreamedResponse {
        /** @var User|null $user */
        $user = Auth::user();

        abort_unless($user instanceof User, 403);
        abort_unless($access->canAccess($content->training, $user), 403);

        $disk = Storage::disk($store->disk());

        abort_unless($disk->exists($content->path), 404);

        return $disk->response($content->path, $this->filename($content));
    }

    /**
     * The stored name is an opaque ULID; the download deserves the title the
     * farmer actually gave the file.
     */
    private function filename(TrainingContent $content): string
    {
        $extension = pathinfo($content->path, PATHINFO_EXTENSION);

        return Str::slug($content->title).'.'.Str::lower((string) $extension);
    }
}

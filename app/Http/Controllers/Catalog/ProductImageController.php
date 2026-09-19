<?php

declare(strict_types=1);

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\ProductImage;
use App\Services\Catalog\ProductImageStore;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves a product image from the private disk.
 *
 * Product images are public information once the product is published, so
 * there is no entitlement to check here — but routing them through a
 * controller means the application never depends on storage:link, which is the
 * one piece of the Windows setup that could not be tested from here.
 */
final class ProductImageController extends Controller
{
    public function __invoke(ProductImage $image, ProductImageStore $store): StreamedResponse
    {
        $disk = Storage::disk($store->disk());

        abort_unless($disk->exists($image->path), 404);

        return $disk->response(
            $image->path,
            headers: [
                // Filenames are immutable ULIDs, so a long cache is safe and
                // keeps the catalogue from re-fetching on every scroll.
                'Cache-Control' => 'public, max-age=31536000, immutable',
            ],
        );
    }
}

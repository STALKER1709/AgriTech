<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores and removes product images.
 *
 * Files land on a private disk and are served by a controller, never by a
 * direct file URL. That removes any dependence on the storage:link symlink,
 * whose behaviour on Windows could not be verified here, and it is the same
 * shape business rule RG05 will need for paid training content.
 *
 * Uploaded names are thrown away. A name chosen by whoever uploads the file is
 * an instruction to the filesystem, not information worth keeping.
 */
final class ProductImageStore
{
    public function add(Product $product, UploadedFile $file): ProductImage
    {
        $extension = Str::lower($file->extension() ?: $file->getClientOriginalExtension());
        $name = Str::ulid()->toString().'.'.$extension;

        $path = $file->storeAs($this->directory($product), $name, ['disk' => $this->disk()]);

        return ProductImage::create([
            'product_id' => $product->id,
            'path' => (string) $path,
            'position' => $this->nextPosition($product),
        ]);
    }

    public function remove(ProductImage $image): void
    {
        Storage::disk($this->disk())->delete($image->path);

        $image->delete();
    }

    /**
     * Remove every file a product owns, used when the product itself goes.
     */
    public function removeAll(Product $product): void
    {
        $product->loadMissing('images');

        foreach ($product->images as $image) {
            $this->remove($image);
        }
    }

    public function disk(): string
    {
        return (string) config('catalog.images.disk', 'local');
    }

    private function directory(Product $product): string
    {
        return trim((string) config('catalog.images.directory', 'products'), '/').'/'.$product->id;
    }

    private function nextPosition(Product $product): int
    {
        return (int) $product->images()->max('position') + 1;
    }
}

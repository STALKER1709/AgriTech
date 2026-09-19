<?php

declare(strict_types=1);

use App\Livewire\Farmer\ProductForm;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('local');

    $this->farmer = User::factory()->farmer()->create();
    $this->category = Category::factory()->create();
    $this->product = Product::factory()->draft()->forFarmer($this->farmer)->create();
});

function fillProductForm(Livewire\Features\SupportTesting\Testable $component): Livewire\Features\SupportTesting\Testable
{
    return $component
        ->set('name', 'Tomate fraîche de saison')
        ->set('description', 'Tomates cultivées en plein champ et récoltées à maturité pour la vente.')
        ->set('unit_price', '800')
        ->set('unit', 'kg')
        ->set('stock_quantity', '120');
}

it('stores an uploaded image under a generated name', function () {
    Livewire::actingAs($this->farmer)
        ->test(ProductForm::class, ['product' => $this->product])
        ->set('uploads', [UploadedFile::fake()->image('ma-photo-perso.jpg', 800, 600)])
        ->call('save')
        ->assertHasNoErrors();

    $image = ProductImage::query()->sole();

    expect($image->product_id)->toBe($this->product->id);
    Storage::disk('local')->assertExists($image->path);

    // The name chosen by whoever uploads the file is an instruction to the
    // filesystem, not information worth keeping.
    expect($image->path)->not->toContain('ma-photo-perso');
    expect($image->path)->toStartWith('products/'.$this->product->id.'/');
});

it('refuses a file that is not really an image, whatever its extension', function () {
    $disguised = UploadedFile::fake()->createWithContent('photo.jpg', '<?php echo "bonjour"; ?>');

    Livewire::actingAs($this->farmer)
        ->test(ProductForm::class, ['product' => $this->product])
        ->set('uploads', [$disguised])
        ->call('save')
        ->assertHasErrors('uploads.0');

    expect(ProductImage::query()->count())->toBe(0);
});

it('refuses an image beyond the size limit', function () {
    $tooBig = UploadedFile::fake()->image('enorme.jpg')->size(
        (int) config('catalog.images.max_kilobytes') + 1,
    );

    Livewire::actingAs($this->farmer)
        ->test(ProductForm::class, ['product' => $this->product])
        ->set('uploads', [$tooBig])
        ->call('save')
        ->assertHasErrors('uploads.0');
});

it('refuses an image beyond the dimension limit', function () {
    $huge = UploadedFile::fake()->image('immense.jpg', 5000, 5000);

    Livewire::actingAs($this->farmer)
        ->test(ProductForm::class, ['product' => $this->product])
        ->set('uploads', [$huge])
        ->call('save')
        ->assertHasErrors('uploads.0');
});

it('refuses more images than a product may hold', function () {
    $max = (int) config('catalog.images.max_per_product');

    $files = collect(range(1, $max + 1))
        ->map(fn (int $index): UploadedFile => UploadedFile::fake()->image("photo-{$index}.jpg", 400, 300))
        ->all();

    Livewire::actingAs($this->farmer)
        ->test(ProductForm::class, ['product' => $this->product])
        ->set('uploads', $files)
        ->call('save')
        ->assertHasErrors('uploads');
});

it('serves an image through the controller, not a file URL', function () {
    Livewire::actingAs($this->farmer)
        ->test(ProductForm::class, ['product' => $this->product])
        ->set('uploads', [UploadedFile::fake()->image('photo.jpg', 400, 300)])
        ->call('save');

    $image = ProductImage::query()->sole();

    expect($image->url())->toBe(route('catalog.image', ['image' => $image->id]));

    $response = $this->get($image->url())->assertOk();

    // Symfony reorders the directives, so the content is what matters.
    $cacheControl = $response->headers->get('Cache-Control');

    expect($cacheControl)->toContain('public');
    expect($cacheControl)->toContain('max-age=31536000');
    expect($cacheControl)->toContain('immutable');
});

it('answers 404 when the file behind an image has gone', function () {
    $image = ProductImage::factory()->create([
        'product_id' => $this->product->id,
        'path' => 'products/'.$this->product->id.'/disparue.jpg',
    ]);

    $this->get(route('catalog.image', ['image' => $image->id]))->assertNotFound();
});

it('deletes the file when the image is removed', function () {
    Livewire::actingAs($this->farmer)
        ->test(ProductForm::class, ['product' => $this->product])
        ->set('uploads', [UploadedFile::fake()->image('photo.jpg', 400, 300)])
        ->call('save');

    $image = ProductImage::query()->sole();
    $path = $image->path;

    Livewire::actingAs($this->farmer)
        ->test(ProductForm::class, ['product' => $this->product])
        ->call('removeImage', $image->id);

    Storage::disk('local')->assertMissing($path);
    expect(ProductImage::query()->count())->toBe(0);
});

it('never lets a farmer remove another farmer\'s image', function () {
    $other = Product::factory()->draft()->create();
    $image = ProductImage::factory()->create(['product_id' => $other->id]);

    Livewire::actingAs($this->farmer)
        ->test(ProductForm::class, ['product' => $this->product])
        ->call('removeImage', $image->id)
        ->assertForbidden();

    expect(ProductImage::query()->find($image->id))->not->toBeNull();
});

<?php

declare(strict_types=1);

namespace App\Livewire\Farmer;

use App\Enums\ProductUnit;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use App\Services\Catalog\ProductImageStore;
use App\Services\Catalog\ProductService;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Title('Fiche produit')]
class ProductForm extends Component
{
    use WithFileUploads;

    public ?Product $product = null;

    public string $name = '';

    /**
     * Le champ reçoit ce que le navigateur envoie : un `<select>` renvoie
     * toujours une chaîne, et une propriété typée `?int` la refuse sous
     * `strict_types`. La règle `integer` la valide, et `save()` la convertit
     * une fois, au moment d'écrire.
     */
    public int|string|null $category_id = null;

    public string $description = '';

    public string $unit_price = '';

    public string $unit = ProductUnit::Kilogram->value;

    public string $stock_quantity = '';

    /**
     * @var array<int, TemporaryUploadedFile>
     */
    public array $uploads = [];

    public function mount(?Product $product = null): void
    {
        if ($product?->exists) {
            $this->authorize('update', $product);

            $this->product = $product;
            $this->name = $product->name;
            $this->category_id = $product->category_id;
            $this->description = $product->description;
            $this->unit_price = (string) $product->unit_price->amount;
            $this->unit = $product->unit->value;
            $this->stock_quantity = $product->stock_quantity->toDecimalString();

            return;
        }

        $this->authorize('create', Product::class);
    }

    /**
     * @return Collection<int, Category>
     */
    public function categories(): Collection
    {
        return Category::query()->orderBy('name')->get();
    }

    /**
     * @return array<int, ProductUnit>
     */
    public function units(): array
    {
        return ProductUnit::cases();
    }

    /**
     * @return Collection<int, ProductImage>
     */
    public function images(): Collection
    {
        return $this->product?->images()->orderBy('position')->get() ?? collect();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        $images = config('catalog.images');

        return [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'description' => ['required', 'string', 'min:20', 'max:5000'],
            // Business rule RG10: whole francs. A decimal here would be
            // rejected by MoneyCast anyway, so it is refused up front with a
            // message the farmer can act on.
            'unit_price' => ['required', 'regex:/^\d+$/', 'min:1'],
            'unit' => ['required', 'string', 'in:'.implode(',', array_column(ProductUnit::cases(), 'value'))],
            'stock_quantity' => ['required', 'regex:/^\d+(\.\d{1,3})?$/'],
            'uploads' => ['array', 'max:'.$images['max_per_product']],
            // `image` checks the real content, not the extension in the name.
            'uploads.*' => [
                'image',
                'mimes:'.implode(',', $images['mimes']),
                'max:'.$images['max_kilobytes'],
                'dimensions:max_width='.$images['max_width'].',max_height='.$images['max_height'],
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'name' => __('nom du produit'),
            'category_id' => __('catégorie'),
            'description' => __('description'),
            'unit_price' => __('prix unitaire'),
            'unit' => __('unité'),
            'stock_quantity' => __('quantité en stock'),
            'uploads' => __('images'),
            'uploads.*' => __('image'),
        ];
    }

    public function save(ProductService $products, ProductImageStore $store): void
    {
        $validated = $this->validate();

        $attributes = [
            'name' => $validated['name'],
            'category_id' => (int) $validated['category_id'],
            'description' => $validated['description'],
            'unit_price' => (int) $validated['unit_price'],
            'unit' => $validated['unit'],
            'stock_quantity' => $validated['stock_quantity'],
        ];

        if ($this->product?->exists) {
            $this->authorize('update', $this->product);
            $product = $products->update($this->product, $attributes);
        } else {
            $this->authorize('create', Product::class);
            $product = $products->create($this->farmer(), $attributes);
        }

        foreach ($this->uploads as $upload) {
            $store->add($product, $upload);
        }

        $this->uploads = [];

        Flux::toast(variant: 'success', text: __('Fiche enregistrée.'));

        $this->redirectRoute('farmer.products', navigate: true);
    }

    public function removeImage(int $imageId, ProductImageStore $store): void
    {
        $image = ProductImage::query()->findOrFail($imageId);

        abort_unless($this->product !== null && $image->product_id === $this->product->id, 403);

        $this->authorize('update', $this->product);

        $store->remove($image);
    }

    private function farmer(): User
    {
        $farmer = Auth::user();

        abort_unless($farmer instanceof User, 403);

        return $farmer;
    }

    public function render(): mixed
    {
        return view('livewire.farmer.product-form');
    }
}

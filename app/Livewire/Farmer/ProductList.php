<?php

declare(strict_types=1);

namespace App\Livewire\Farmer;

use App\Enums\PublicationStatus;
use App\Models\Product;
use App\Models\User;
use App\Services\Catalog\ProductService;
use App\Services\Catalog\PublicationService;
use DomainException;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Mes produits')]
class ProductList extends Component
{
    use WithPagination;

    #[Url]
    public string $status = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Product::class);
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function products(): LengthAwarePaginator
    {
        return Product::query()
            ->with(['category', 'images'])
            ->where('farmer_id', $this->farmer()->id)
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->orderByDesc('updated_at')
            ->paginate(10);
    }

    /**
     * @return array<int, PublicationStatus>
     */
    public function statuses(): array
    {
        return PublicationStatus::cases();
    }

    public function submit(int $productId, PublicationService $publications): void
    {
        $product = Product::query()->findOrFail($productId);

        $this->authorize('submit', $product);

        try {
            $target = $publications->submit($product, $this->farmer());
        } catch (DomainException $exception) {
            Flux::toast(variant: 'danger', text: $exception->getMessage());

            return;
        }

        Flux::toast(
            variant: 'success',
            text: $target === PublicationStatus::Published
                ? __('Produit publié.')
                : __('Produit soumis à modération.'),
        );
    }

    public function archive(int $productId, ProductService $products): void
    {
        $product = Product::query()->findOrFail($productId);

        $this->authorize('archive', $product);

        $products->archive($product);

        Flux::toast(variant: 'success', text: __('Produit archivé.'));
    }

    private function farmer(): User
    {
        $farmer = Auth::user();

        abort_unless($farmer instanceof User, 403);

        return $farmer;
    }

    public function render(): mixed
    {
        return view('livewire.farmer.product-list', ['products' => $this->products()]);
    }
}

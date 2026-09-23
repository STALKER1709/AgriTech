<?php

declare(strict_types=1);

namespace App\Livewire\Catalog;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\Orders\CartService;
use App\Support\CameroonRegions;
use App\Support\Quantity;
use DomainException;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * The public catalogue.
 *
 * Open to visitors: browsing is what brings people in, and an account should
 * only be needed to buy.
 */
#[Layout('layouts::public')]
#[Title('Catalogue')]
class Browse extends Component
{
    /**
     * How many products a page shows, and how many more each "load more"
     * adds. The mockup has no pagination: it lengthens the list.
     */
    private const int PAGE_SIZE = 12;

    #[Url]
    public string $search = '';

    #[Url]
    public string $category = '';

    #[Url]
    public string $region = '';

    #[Url]
    public string $sort = 'recent';

    public int $shown = self::PAGE_SIZE;

    public function updatedSearch(): void
    {
        $this->resetLength();
    }

    public function updatedCategory(): void
    {
        $this->resetLength();
    }

    public function updatedRegion(): void
    {
        $this->resetLength();
    }

    public function updatedSort(): void
    {
        $this->resetLength();
    }

    /**
     * @return EloquentCollection<int, Product>
     */
    public function products(): EloquentCollection
    {
        return $this->query()
            // Eager loaded on purpose: a grid of products that lazy-loads its
            // farmer and category is the textbook N+1, and preventLazyLoading
            // would turn it into an exception in local development anyway.
            ->with(['category', 'images', 'farmer.farmerProfile'])
            ->limit($this->shown)
            ->get();
    }

    public function total(): int
    {
        return $this->query()->count();
    }

    public function remaining(): int
    {
        return max(0, $this->total() - $this->shown);
    }

    public function loadMore(): void
    {
        $this->shown += self::PAGE_SIZE;
    }

    /**
     * How many filters are on, for the badge of the "Filtrer" button.
     */
    public function activeFilterCount(): int
    {
        return collect([$this->category, $this->region])
            ->filter(fn (string $value): bool => $value !== '')
            ->count();
    }

    /**
     * @return Collection<int, Category>
     */
    public function categories(): Collection
    {
        return Category::query()->orderBy('name')->get();
    }

    /**
     * @return array<int, string>
     */
    public function regions(): array
    {
        return CameroonRegions::all();
    }

    /**
     * @return array<string, string>
     */
    public function sorts(): array
    {
        return [
            'recent' => __('Plus récents'),
            'price_asc' => __('Prix croissant'),
            'price_desc' => __('Prix décroissant'),
            'name' => __('Nom'),
        ];
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'category', 'region', 'sort']);
        $this->resetLength();
    }

    /**
     * The quick "+" of the product card.
     *
     * A visitor is sent to log in; anyone else meets the same rules as the
     * product page, because it is the same service that answers.
     */
    public function addToCart(int $productId, CartService $carts): void
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            session(['url.intended' => route('catalog.browse')]);

            $this->redirectRoute('login', navigate: true);

            return;
        }

        abort_unless($user->isClient() && $user->isActive(), 403);

        $product = Product::query()->visibleToPublic()->whereKey($productId)->first();

        if (! $product instanceof Product) {
            Flux::toast(variant: 'danger', text: __('Ce produit n\'est plus proposé à la vente.'));

            return;
        }

        try {
            $carts->add($user, $product, Quantity::fromInteger(1));
        } catch (DomainException $exception) {
            Flux::toast(variant: 'danger', text: $exception->getMessage());

            return;
        }

        Flux::toast(variant: 'success', text: __(':product ajouté au panier.', ['product' => $product->name]));
    }

    /**
     * @return Builder<Product>
     */
    private function query(): Builder
    {
        return Product::query()
            ->visibleToPublic()
            ->when($this->search !== '', function ($query): void {
                $term = '%'.$this->search.'%';

                $query->where(fn ($inner) => $inner
                    ->where('name', 'like', $term)
                    ->orWhere('description', 'like', $term));
            })
            ->when($this->category !== '', fn ($query) => $query->whereHas(
                'category',
                fn ($category) => $category->where('slug', $this->category),
            ))
            ->when($this->region !== '', fn ($query) => $query->whereHas(
                'farmer.farmerProfile',
                fn ($profile) => $profile->where('region', $this->region),
            ))
            ->when($this->sort === 'price_asc', fn ($query) => $query->orderBy('unit_price'))
            ->when($this->sort === 'price_desc', fn ($query) => $query->orderByDesc('unit_price'))
            ->when($this->sort === 'name', fn ($query) => $query->orderBy('name'))
            ->when($this->sort === 'recent', fn ($query) => $query->orderByDesc('created_at'));
    }

    private function resetLength(): void
    {
        $this->shown = self::PAGE_SIZE;
    }

    public function render(): mixed
    {
        return view('livewire.catalog.browse', ['products' => $this->products()]);
    }
}

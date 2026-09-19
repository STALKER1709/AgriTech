<?php

declare(strict_types=1);

namespace App\Livewire\Catalog;

use App\Models\Category;
use App\Models\Product;
use App\Support\CameroonRegions;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

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
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $category = '';

    #[Url]
    public string $region = '';

    #[Url]
    public string $sort = 'recent';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategory(): void
    {
        $this->resetPage();
    }

    public function updatedRegion(): void
    {
        $this->resetPage();
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function products(): LengthAwarePaginator
    {
        return Product::query()
            // Eager loaded on purpose: a grid of products that lazy-loads its
            // farmer and category is the textbook N+1, and preventLazyLoading
            // would turn it into an exception in local development anyway.
            ->with(['category', 'images', 'farmer.farmerProfile'])
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
            ->when($this->sort === 'recent', fn ($query) => $query->orderByDesc('created_at'))
            ->paginate(12);
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

    public function resetFilters(): void
    {
        $this->reset(['search', 'category', 'region', 'sort']);
        $this->resetPage();
    }

    public function render(): mixed
    {
        return view('livewire.catalog.browse', ['products' => $this->products()]);
    }
}

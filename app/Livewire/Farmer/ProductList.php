<?php

declare(strict_types=1);

namespace App\Livewire\Farmer;

use App\Enums\PublicationStatus;
use App\Enums\SubOrderStatus;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\Catalog\ProductService;
use App\Services\Catalog\PublicationService;
use App\Support\Quantity;
use DomainException;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
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

    #[Url]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

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
            ->when($this->search !== '', function ($query): void {
                $term = '%'.str_replace(['%', '_'], ['\%', '\_'], $this->search).'%';

                $query->where(fn ($inner) => $inner
                    ->where('name', 'like', $term)
                    ->orWhere('description', 'like', $term));
            })
            ->orderByDesc('updated_at')
            ->paginate(10);
    }

    /**
     * Combien de fiches pour chaque statut, pour que les pastilles ne
     * promettent pas une liste qu'elles ne peuvent pas remplir.
     *
     * @return array<string, int>
     */
    public function counts(): array
    {
        $counts = Product::query()
            ->where('farmer_id', $this->farmer()->id)
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $byStatus = [];

        foreach (PublicationStatus::cases() as $case) {
            $byStatus[$case->value] = (int) $counts->get($case->value, 0);
        }

        $byStatus['all'] = array_sum($byStatus);

        return $byStatus;
    }

    /**
     * Les quantités vendues ce mois-ci, par produit.
     *
     * Une seule requête groupée plutôt qu'une par carte, et seules les
     * sous-commandes réellement payées comptent : une commande annulée n'a
     * jamais été une vente.
     *
     * @return array<int, Quantity>
     */
    public function soldThisMonth(): array
    {
        $since = now(config('app.timezone'))->startOfMonth()->utc();

        // `order_items.quantity` est un decimal(12,3) : la somme revient sous
        // la même forme, « 25.000 », et non en millièmes. La convertir avec
        // `fromThousandths()` diviserait tout par mille en silence.
        /** @var Collection<int, int|string> $rows */
        $rows = OrderItem::query()
            ->selectRaw('order_items.product_id, sum(order_items.quantity) as sold')
            ->join('sub_orders', 'sub_orders.id', '=', 'order_items.sub_order_id')
            ->where('sub_orders.farmer_id', $this->farmer()->id)
            ->whereIn('sub_orders.status', [SubOrderStatus::Paid, SubOrderStatus::Preparing, SubOrderStatus::Delivered])
            ->where('sub_orders.created_at', '>=', $since)
            ->groupBy('order_items.product_id')
            ->pluck('sold', 'order_items.product_id');

        $sold = [];

        foreach ($rows as $productId => $decimal) {
            $sold[(int) $productId] = Quantity::fromString(number_format((float) $decimal, 3, '.', ''));
        }

        return $sold;
    }

    public function activeFilterCount(): int
    {
        return (int) ($this->status !== '') + (int) ($this->search !== '');
    }

    public function resetFilters(): void
    {
        $this->reset(['status', 'search']);
        $this->resetPage();
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

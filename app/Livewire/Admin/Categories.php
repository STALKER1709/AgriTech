<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\User;
use App\Services\Catalog\CategoryService;
use DomainException;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Catégories')]
class Categories extends Component
{
    public string $newName = '';

    public ?int $editing = null;

    public string $editedName = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Category::class);
    }

    /**
     * @return Collection<int, Category>
     */
    public function categories(): Collection
    {
        return Category::query()
            ->withCount('products')
            ->orderBy('name')
            ->get();
    }

    public function create(CategoryService $categories): void
    {
        $this->authorize('create', Category::class);

        $this->validate([
            'newName' => ['required', 'string', 'min:3', 'max:255', 'unique:categories,name'],
        ], attributes: ['newName' => __('nom de la catégorie')]);

        $categories->create($this->newName, $this->admin());

        $this->newName = '';

        Flux::toast(variant: 'success', text: __('Catégorie créée.'));
    }

    public function edit(int $categoryId): void
    {
        $category = Category::query()->findOrFail($categoryId);

        $this->authorize('update', $category);

        $this->editing = $categoryId;
        $this->editedName = $category->name;
    }

    public function cancel(): void
    {
        $this->editing = null;
        $this->editedName = '';
    }

    public function rename(CategoryService $categories): void
    {
        $category = Category::query()->findOrFail($this->editing);

        $this->authorize('update', $category);

        $this->validate([
            'editedName' => ['required', 'string', 'min:3', 'max:255', 'unique:categories,name,'.$category->id],
        ], attributes: ['editedName' => __('nom de la catégorie')]);

        $categories->rename($category, $this->editedName, $this->admin());

        $this->cancel();

        Flux::toast(variant: 'success', text: __('Catégorie renommée.'));
    }

    public function delete(int $categoryId, CategoryService $categories): void
    {
        $category = Category::query()->findOrFail($categoryId);

        $this->authorize('delete', $category);

        try {
            $categories->delete($category, $this->admin());
        } catch (DomainException $exception) {
            Flux::toast(variant: 'danger', text: $exception->getMessage());

            return;
        }

        Flux::toast(variant: 'success', text: __('Catégorie supprimée.'));
    }

    private function admin(): User
    {
        $admin = Auth::user();

        abort_unless($admin instanceof User, 403);

        return $admin;
    }

    public function render(): mixed
    {
        return view('livewire.admin.categories', ['categories' => $this->categories()]);
    }
}

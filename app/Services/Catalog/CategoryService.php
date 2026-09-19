<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Models\Category;
use App\Models\User;
use App\Services\Admin\AuditLogger;
use App\Support\SlugGenerator;
use DomainException;
use Illuminate\Support\Facades\DB;

final class CategoryService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function create(string $name, User $admin): Category
    {
        return DB::transaction(function () use ($name, $admin): Category {
            $category = Category::create([
                'name' => $name,
                'slug' => SlugGenerator::for(Category::class, $name),
            ]);

            $this->audit->record(
                action: AuditLogger::CATEGORY_CREATED,
                auditable: $category,
                after: ['name' => $category->name, 'slug' => $category->slug],
                actor: $admin,
            );

            return $category;
        });
    }

    public function rename(Category $category, string $name, User $admin): Category
    {
        return DB::transaction(function () use ($category, $name, $admin): Category {
            $before = ['name' => $category->name, 'slug' => $category->slug];

            // The slug stays put: it is in every catalogue URL already handed
            // out, and a rename is a label change, not a new category.
            $category->forceFill(['name' => $name])->save();

            $this->audit->record(
                action: AuditLogger::CATEGORY_UPDATED,
                auditable: $category,
                before: $before,
                after: ['name' => $category->name, 'slug' => $category->slug],
                actor: $admin,
            );

            return $category->refresh();
        });
    }

    public function delete(Category $category, User $admin): void
    {
        if ($category->products()->exists()) {
            throw new DomainException(
                'Cette catégorie contient des produits : elle ne peut pas être supprimée.',
            );
        }

        DB::transaction(function () use ($category, $admin): void {
            $before = ['name' => $category->name, 'slug' => $category->slug];

            $this->audit->record(
                action: AuditLogger::CATEGORY_DELETED,
                auditable: $category,
                before: $before,
                actor: $admin,
            );

            $category->delete();
        });
    }
}

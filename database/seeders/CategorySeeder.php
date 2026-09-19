<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Categories covering what is actually farmed and sold in Cameroon.
     *
     * @var array<int, string>
     */
    private const array CATEGORIES = [
        'Céréales et légumineuses',
        'Tubercules et racines',
        'Fruits',
        'Légumes',
        'Élevage et volaille',
        'Produits de la pêche',
        'Produits transformés',
        'Cultures de rente',
        'Semences et plants',
        'Intrants agricoles',
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $name) {
            Category::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name],
            );
        }
    }
}

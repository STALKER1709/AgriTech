<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'slug' => 'mensuel',
                'name' => 'Mensuel',
                'description' => 'Accès à toutes les formations incluses, pendant 30 jours.',
                'price' => 5000,
                'duration_days' => 30,
                'is_active' => true,
            ],
            [
                'slug' => 'trimestriel',
                'name' => 'Trimestriel',
                'description' => 'Accès à toutes les formations incluses, pendant 90 jours.',
                'price' => 12000,
                'duration_days' => 90,
                'is_active' => true,
            ],
            [
                'slug' => 'annuel',
                'name' => 'Annuel',
                'description' => 'Accès à toutes les formations incluses, pendant un an.',
                'price' => 40000,
                'duration_days' => 365,
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}

<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Reference data first, then the demo walkthrough set, which depends on it.
     */
    public function run(): void
    {
        $this->call([
            PrivilegeSeeder::class,
            SuperAdminSeeder::class,
            CategorySeeder::class,
            SettingSeeder::class,
            SubscriptionPlanSeeder::class,
            DemoSeeder::class,
        ]);
    }
}

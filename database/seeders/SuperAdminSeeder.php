<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Privilege;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Creates the single super-administrator and grants every privilege.
 *
 * Its credentials are fixed so the demo walkthrough in the README stays
 * reproducible; this only ever runs against a local database.
 */
class SuperAdminSeeder extends Seeder
{
    public const string EMAIL = 'admin@agritech.local';

    public const string PASSWORD = 'password';

    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => self::EMAIL],
            [
                'first_name' => 'Super',
                'last_name' => 'Administrateur',
                'phone' => '+237600000001',
                'password' => self::PASSWORD,
                'role' => UserRole::Admin,
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
            ],
        );

        $admin->privileges()->sync(Privilege::query()->pluck('id'));
    }
}

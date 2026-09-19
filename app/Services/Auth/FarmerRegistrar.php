<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\FarmerProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Creates a farmer account together with its farm profile.
 *
 * Farmer sign-up is not client sign-up with a different role: it writes two
 * rows and lands on a different status. The account starts at PendingPayment
 * because business rule RG02 only lets it move on to review once the
 * registration fee has actually been paid — which phase 3 wires up.
 *
 * Both rows are written in one transaction, so a failure never leaves an
 * account without its profile.
 */
final class FarmerRegistrar
{
    /**
     * @param  array{
     *     first_name: string,
     *     last_name: string,
     *     email: string,
     *     phone: string,
     *     password: string,
     *     farm_name: string,
     *     region: string,
     *     city: string,
     *     description?: string|null,
     * }  $attributes
     */
    public function register(array $attributes): User
    {
        return DB::transaction(function () use ($attributes): User {
            $farmer = User::create([
                'first_name' => $attributes['first_name'],
                'last_name' => $attributes['last_name'],
                'email' => mb_strtolower($attributes['email']),
                'phone' => $attributes['phone'],
                'password' => $attributes['password'],
                'role' => UserRole::Farmer,
                'status' => UserStatus::PendingPayment,
            ]);

            FarmerProfile::create([
                'user_id' => $farmer->id,
                'farm_name' => $attributes['farm_name'],
                'region' => $attributes['region'],
                'city' => $attributes['city'],
                'description' => $attributes['description'] ?? null,
            ]);

            return $farmer;
        });
    }
}

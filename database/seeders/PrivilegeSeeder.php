<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Privilege;
use Illuminate\Database\Seeder;

class PrivilegeSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Privilege::catalogue() as $code => $label) {
            Privilege::updateOrCreate(['code' => $code], ['label' => $label]);
        }
    }
}

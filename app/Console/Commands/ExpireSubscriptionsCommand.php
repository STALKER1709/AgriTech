<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Subscriptions\ExpireSubscriptions;
use Illuminate\Console\Command;

final class ExpireSubscriptionsCommand extends Command
{
    protected $signature = 'agritech:subscriptions:expire';

    protected $description = 'Passe en « expiré » les abonnements dont le terme est dépassé';

    public function handle(ExpireSubscriptions $expirations): int
    {
        $count = $expirations->expire();

        if ($count === 0) {
            $this->info('Aucun abonnement à expirer.');

            return self::SUCCESS;
        }

        $this->info($count.' abonnement(s) expiré(s).');

        return self::SUCCESS;
    }
}

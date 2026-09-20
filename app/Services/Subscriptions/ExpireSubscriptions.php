<?php

declare(strict_types=1);

namespace App\Services\Subscriptions;

use App\Models\Subscription;
use Illuminate\Support\Facades\DB;

/**
 * Closes the subscriptions whose term has passed.
 *
 * The status alone is not the truth: an Active subscription whose ends_at is
 * behind us grants nothing (the model already ignores it) but keeps lying on
 * the screens. The scheduler sweeps them, in one update, so the record matches
 * what the access checks have been saying all along.
 */
final class ExpireSubscriptions
{
    /**
     * @return int the number of subscriptions closed
     */
    public function __invoke(): int
    {
        return $this->expire();
    }

    public function expire(): int
    {
        return DB::transaction(function (): int {
            $lapsed = Subscription::query()
                ->lapsed()
                ->lockForUpdate()
                ->get();

            foreach ($lapsed as $subscription) {
                $subscription->expire();
            }

            return $lapsed->count();
        });
    }
}

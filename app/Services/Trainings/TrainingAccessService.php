<?php

declare(strict_types=1);

namespace App\Services\Trainings;

use App\Models\Training;
use App\Models\TrainingPurchase;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Who can read a training, decided nowhere else.
 *
 * Business rule RG05: content is reachable through a successful purchase or
 * through an active subscription, when the training is part of it. The model
 * carries the same question for convenience, but this service is the single
 * home of the answer the controllers act on.
 */
final class TrainingAccessService
{
    /**
     * Whether the client may open the training's content.
     */
    public function canAccess(Training $training, User $client): bool
    {
        return $training->isAccessibleBy($client);
    }

    /**
     * Record a purchase once. Returns the existing row when the training was
     * already bought, because a late callback or a replay is not a second sale.
     *
     * Called inside the transaction that confirmed the payment, under the
     * database's unique constraint on (client_id, training_id).
     */
    public function recordPurchase(Training $training, User $client): TrainingPurchase
    {
        return DB::transaction(function () use ($training, $client): TrainingPurchase {
            $existing = TrainingPurchase::query()
                ->where('client_id', $client->id)
                ->where('training_id', $training->id)
                ->first();

            if ($existing instanceof TrainingPurchase) {
                return $existing;
            }

            return TrainingPurchase::create([
                'client_id' => $client->id,
                'training_id' => $training->id,
                'amount' => $training->price,
                'purchased_at' => now(),
            ]);
        });
    }
}

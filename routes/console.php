<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Tâches planifiées
|--------------------------------------------------------------------------
|
| Lancées en local par `php artisan schedule:work`.
|
*/

// Chases up payments nobody answered, so that a farmer who actually paid is
// never left blocked by a callback that went missing.
Schedule::command('agritech:payments:reconcile')
    ->everyFiveMinutes()
    ->withoutOverlapping();

// Closes the orders clients placed and never paid for. Nothing is released —
// business rule RG04 never moved the stock in the first place — but an order
// list full of abandoned carts helps nobody.
Schedule::command('agritech:orders:cancel-expired')
    ->everyFiveMinutes()
    ->withoutOverlapping();

// Closes the subscriptions whose term has passed, so that the record matches
// what the access checks have been saying all along.
Schedule::command('agritech:subscriptions:expire')
    ->everyFiveMinutes()
    ->withoutOverlapping();

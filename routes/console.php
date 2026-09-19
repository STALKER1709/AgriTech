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

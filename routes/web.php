<?php

use App\Http\Controllers\DashboardController;
use App\Livewire\Account\Status as AccountStatus;
use App\Livewire\Auth\RegisterFarmer;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

/*
|--------------------------------------------------------------------------
| Inscription agriculteur
|--------------------------------------------------------------------------
|
| Distinct from the Fortify registration, which creates clients: a farmer
| account also carries a farm profile and starts out awaiting payment.
|
*/

Route::middleware('guest')->group(function (): void {
    Route::get('inscription/agriculteur', RegisterFarmer::class)->name('register.farmer');
});

/*
|--------------------------------------------------------------------------
| Espaces authentifiés
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function (): void {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('mon-compte/statut', AccountStatus::class)->name('account.status');
});

Route::middleware(['auth', 'role:client'])
    ->prefix('client')
    ->name('client.')
    ->group(function (): void {
        Route::view('tableau-de-bord', 'client.dashboard')->name('dashboard');
    });

Route::middleware(['auth', 'role:farmer', 'account.active'])
    ->prefix('agriculteur')
    ->name('farmer.')
    ->group(function (): void {
        Route::view('tableau-de-bord', 'farmer.dashboard')->name('dashboard');
    });

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::view('tableau-de-bord', 'admin.dashboard')->name('dashboard');
    });

require __DIR__.'/settings.php';

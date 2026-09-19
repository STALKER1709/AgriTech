<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Payments\WebhookController;
use App\Livewire\Account\Status as AccountStatus;
use App\Livewire\Admin\AuditTrail;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\FarmerValidation;
use App\Livewire\Admin\Privileges as AdminPrivileges;
use App\Livewire\Admin\Settings as AdminSettings;
use App\Livewire\Admin\Users as AdminUsers;
use App\Livewire\Auth\RegisterFarmer;
use App\Livewire\Payments\Pending as PaymentPending;
use App\Livewire\Payments\Sandbox as PaymentSandbox;
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

/*
|--------------------------------------------------------------------------
| Paiement
|--------------------------------------------------------------------------
|
| The sandbox stands in for an operator's hosted checkout, and the pending
| screen reports what the server has confirmed. Neither of them can move a
| payment forward: business rule RG06 reserves that for the webhook below and
| for the reconciliation task.
|
*/

Route::middleware(['auth', 'throttle:payments'])->group(function (): void {
    Route::get('paiement-test/{payment:provider_reference}', PaymentSandbox::class)
        ->name('payments.sandbox');

    Route::get('paiement/{payment:provider_reference}/verification', PaymentPending::class)
        ->name('payments.pending');
});

/*
| Server-to-server, exempt from CSRF in bootstrap/app.php and protected by the
| HMAC signature the gateway puts on every callback.
*/

Route::post('webhooks/paiements', WebhookController::class)->name('webhooks.payment');

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

/*
| The admin role opens this area; it authorises nothing inside it. Every
| action goes through a policy keyed on a privilege — business rule RG07.
*/

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('tableau-de-bord', AdminDashboard::class)->name('dashboard');
        Route::get('agriculteurs-a-valider', FarmerValidation::class)->name('farmers');
        Route::get('utilisateurs', AdminUsers::class)->name('users');
        Route::get('privileges', AdminPrivileges::class)->name('privileges');
        Route::get('parametres', AdminSettings::class)->name('settings');
        Route::get('journal-audit', AuditTrail::class)->name('audit');
    });

require __DIR__.'/settings.php';

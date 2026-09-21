<?php

use App\Http\Controllers\Catalog\ProductImageController;
use App\Http\Controllers\Catalog\TrainingCoverController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Payments\WebhookController;
use App\Http\Controllers\Trainings\TrainingContentController;
use App\Livewire\Account\Status as AccountStatus;
use App\Livewire\Admin\AuditTrail;
use App\Livewire\Admin\Categories as AdminCategories;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\FarmerValidation;
use App\Livewire\Admin\Moderation;
use App\Livewire\Admin\Privileges as AdminPrivileges;
use App\Livewire\Admin\Settings as AdminSettings;
use App\Livewire\Admin\Users as AdminUsers;
use App\Livewire\Auth\RegisterFarmer;
use App\Livewire\Catalog\Browse;
use App\Livewire\Catalog\ProductPage;
use App\Livewire\Client\CartPage;
use App\Livewire\Client\Messages as ClientMessages;
use App\Livewire\Client\MessageThread as ClientMessageThread;
use App\Livewire\Client\MyTrainings as ClientMyTrainings;
use App\Livewire\Client\OrderList as ClientOrderList;
use App\Livewire\Client\OrderPage as ClientOrderPage;
use App\Livewire\Client\Subscriptions as ClientSubscriptions;
use App\Livewire\Farmer\Dashboard as FarmerDashboard;
use App\Livewire\Farmer\Messages as FarmerMessages;
use App\Livewire\Farmer\MessageThread as FarmerMessageThread;
use App\Livewire\Farmer\OrderList as FarmerOrderList;
use App\Livewire\Farmer\ProductForm;
use App\Livewire\Farmer\ProductList;
use App\Livewire\Farmer\TrainingForm as FarmerTrainingForm;
use App\Livewire\Farmer\TrainingList as FarmerTrainingList;
use App\Livewire\Payments\Pending as PaymentPending;
use App\Livewire\Payments\Sandbox as PaymentSandbox;
use App\Livewire\Trainings\Index as TrainingsIndex;
use App\Livewire\Trainings\Page as TrainingPage;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

/*
|--------------------------------------------------------------------------
| Catalogue public
|--------------------------------------------------------------------------
|
| Open to visitors: browsing is what brings people in, and an account should
| only be needed to buy. Images are served by a controller rather than from a
| public file URL, so nothing here depends on storage:link.
|
*/

Route::get('catalogue', Browse::class)->name('catalog.browse');
Route::get('produits/{product:slug}', ProductPage::class)->name('catalog.product');
Route::get('images/produits/{image}', ProductImageController::class)->name('catalog.image');
Route::get('formations', TrainingsIndex::class)->name('trainings.index');
Route::get('images/formations/{training:slug}/couverture', TrainingCoverController::class)->name('trainings.cover');
Route::get('formations/{training:slug}', TrainingPage::class)->name('trainings.show');

/*
|--------------------------------------------------------------------------
| Contenu de formation
|--------------------------------------------------------------------------
|
| The file is streamed by a controller that checks the entitlement (business
| rule RG05); it never sits behind a public file URL. The route itself is
| open — the 403 for someone without the right is what makes the gate real.
|
*/

Route::get('contenus/formation/{content}', TrainingContentController::class)
    ->middleware('auth')
    ->name('trainings.content');

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
        Route::get('panier', CartPage::class)->name('cart');
        Route::get('commandes', ClientOrderList::class)->name('orders');
        Route::get('commandes/{order:reference}', ClientOrderPage::class)->name('orders.show');
        Route::get('formations', ClientMyTrainings::class)->name('trainings');
        Route::get('abonnement', ClientSubscriptions::class)->name('subscriptions');
        Route::get('messages', ClientMessages::class)->name('messages');
        Route::get('messages/{conversation}', ClientMessageThread::class)->name('messages.show');
    });

Route::middleware(['auth', 'role:farmer', 'account.active'])
    ->prefix('agriculteur')
    ->name('farmer.')
    ->group(function (): void {
        Route::get('tableau-de-bord', FarmerDashboard::class)->name('dashboard');
        Route::get('produits', ProductList::class)->name('products');
        Route::get('produits/nouveau', ProductForm::class)->name('products.create');
        Route::get('produits/{product:slug}/modifier', ProductForm::class)->name('products.edit');
        Route::get('formations', FarmerTrainingList::class)->name('trainings');
        Route::get('formations/nouvelle', FarmerTrainingForm::class)->name('trainings.create');
        Route::get('formations/{training:slug}/modifier', FarmerTrainingForm::class)->name('trainings.edit');
        Route::get('commandes', FarmerOrderList::class)->name('orders');
        Route::get('messages', FarmerMessages::class)->name('messages');
        Route::get('messages/{conversation}', FarmerMessageThread::class)->name('messages.show');
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
        Route::get('publications-a-moderer', Moderation::class)->name('moderation');
        Route::get('categories', AdminCategories::class)->name('categories');
        Route::get('journal-audit', AuditTrail::class)->name('audit');
    });

require __DIR__.'/settings.php';

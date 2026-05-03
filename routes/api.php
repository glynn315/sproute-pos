<?php

use App\Http\Controllers\Api\V1\AdminController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Controllers\Api\V1\ExpenseController;
use App\Http\Controllers\Api\V1\InventoryController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\ProductsController;
use App\Http\Controllers\Api\V1\SaleController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use App\Http\Controllers\Api\V1\TenantController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Baligya POS — API v1
|--------------------------------------------------------------------------
|
| Auth:      POST   /api/v1/auth/register
|            POST   /api/v1/auth/login
|            POST   /api/v1/auth/pin-login
|            GET    /api/v1/auth/verify/{token}
|            POST   /api/v1/auth/refresh
|            POST   /api/v1/auth/logout
|            GET    /api/v1/auth/me
|            POST   /api/v1/auth/forgot-password
|            POST   /api/v1/auth/reset-password
|
| Protected: All routes below require auth:sanctum + verified tenant
|
*/

Route::prefix('v1')->group(function () {

    // ─── Public Auth Routes ───────────────────────────────────────────────────
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('register',        [AuthController::class, 'register'])->name('register');
        Route::post('login',           [AuthController::class, 'login'])->name('login');
        Route::post('pin-login',       [AuthController::class, 'pinLogin'])->name('pin-login');
        Route::get('verify/{token}',   [AuthController::class, 'verifyEmail'])->name('verify');
        Route::post('refresh',         [AuthController::class, 'refresh'])->name('refresh');
        Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot-password');
        Route::post('reset-password',  [AuthController::class, 'resetPassword'])->name('reset-password');
    });

    // ─── Authenticated Routes ─────────────────────────────────────────────────
    Route::middleware(['auth:sanctum'])->group(function () {

        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('auth/me',      [AuthController::class, 'me'])->name('auth.me');

        // ─── Verified Tenant Routes ───────────────────────────────────────────
        Route::middleware(['tenant.verified'])->group(function () {

            // Tenant settings
            Route::prefix('tenant')->name('tenant.')->group(function () {
                Route::get('/',    [TenantController::class, 'show'])->name('show');
                Route::put('/',    [TenantController::class, 'update'])->name('update');

                // Self-service subscription (owner/manager-gated inside controller)
                Route::get('subscription',  [SubscriptionController::class, 'current'])->name('subscription.current');
                Route::post('subscription', [SubscriptionController::class, 'change'])->name('subscription.change');
            });

            // Public-to-tenant catalog of available plans
            Route::get('plans', [SubscriptionController::class, 'plans'])->name('plans.index');

            // Invoices (billing monitor)
            Route::prefix('invoices')->name('invoices.')->group(function () {
                Route::get('/',                  [InvoiceController::class, 'index'])->name('index');
                Route::get('/{invoice}',         [InvoiceController::class, 'show'])->name('show');
                Route::post('/{invoice}/pay',    [InvoiceController::class, 'submitReference'])->name('pay');
                Route::post('/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('cancel');
            });

            // Employees (owner/manager only — enforced in controller/request)
            Route::apiResource('employees', EmployeeController::class);

            // Categories
            Route::apiResource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);

            // Products
            Route::prefix('products')->name('products.')->group(function () {
                Route::get('pricelist',     [ProductsController::class, 'pricelist'])->name('pricelist');
                Route::get('low-stock',     [ProductsController::class, 'lowStock'])->name('low-stock');
                Route::get('/',             [ProductsController::class, 'index'])->name('index');
                Route::post('/',            [ProductsController::class, 'store'])->name('store');
                Route::get('/{product}',    [ProductsController::class, 'show'])->name('show');
                Route::put('/{product}',    [ProductsController::class, 'update'])->name('update');
                Route::delete('/{product}', [ProductsController::class, 'destroy'])->name('destroy');
            });

            // Sales / POS
            Route::prefix('sales')->name('sales.')->group(function () {
                Route::get('/',           [SaleController::class, 'index'])->name('index');
                Route::post('/',          [SaleController::class, 'store'])->name('store');
                Route::get('/{sale}',     [SaleController::class, 'show'])->name('show');
                Route::post('/{sale}/void', [SaleController::class, 'void'])->name('void');
            });

            // Expenses
            Route::apiResource('expenses', ExpenseController::class);

            // Inventory
            Route::prefix('inventory')->name('inventory.')->group(function () {
                Route::get('/',     [InventoryController::class, 'index'])->name('logs');
                Route::post('/adjust', [InventoryController::class, 'adjust'])->name('adjust');
            });

            // Dashboard & Reports
            Route::prefix('dashboard')->name('dashboard.')->group(function () {
                Route::get('summary',         [DashboardController::class, 'summary'])->name('summary');
                Route::get('sales-report',    [DashboardController::class, 'salesReport'])->name('sales-report');
                Route::get('expense-report',  [DashboardController::class, 'expenseReport'])->name('expense-report');
                Route::get('top-products',    [DashboardController::class, 'topProducts'])->name('top-products');
            });
        });

        // ─── Super Admin Routes ───────────────────────────────────────────────
        Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {
            Route::get('tenants',                         [AdminController::class, 'tenants'])->name('tenants');
            Route::get('tenants/{tenant}',                [AdminController::class, 'showTenant'])->name('tenants.show');
            Route::post('tenants/{tenant}/verify',        [AdminController::class, 'verifyTenant'])->name('tenants.verify');
            Route::post('tenants/{tenant}/suspend',       [AdminController::class, 'suspendTenant'])->name('tenants.suspend');
            Route::post('tenants/{tenant}/subscription',  [AdminController::class, 'assignSubscription'])->name('tenants.subscription');

            Route::get('plans',            [AdminController::class, 'plans'])->name('plans');
            Route::post('plans',           [AdminController::class, 'createPlan'])->name('plans.create');
            Route::put('plans/{plan}',     [AdminController::class, 'updatePlan'])->name('plans.update');

            Route::get('invoices',                  [AdminController::class, 'invoices'])->name('invoices');
            Route::post('invoices/{invoice}/verify',[AdminController::class, 'verifyInvoice'])->name('invoices.verify');
            Route::post('invoices/{invoice}/reject',[AdminController::class, 'rejectInvoice'])->name('invoices.reject');
        });
    });
});

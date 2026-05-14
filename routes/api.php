<?php

use App\Http\Controllers\Api\V1\AdminController;
use App\Http\Controllers\Api\V1\AdminModuleController;
use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\EateryDashboardController;
use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Controllers\Api\V1\ExpenseController;
use App\Http\Controllers\Api\V1\InventoryController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\MenuItemController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ProductsController;
use App\Http\Controllers\Api\V1\RestaurantTableController;
use App\Http\Controllers\Api\V1\SaleController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use App\Http\Controllers\Api\V1\SupplierController;
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

            // Suppliers
            Route::prefix('suppliers')->name('suppliers.')->group(function () {
                Route::get('active',         [SupplierController::class, 'active'])->name('active');
                Route::get('/',              [SupplierController::class, 'index'])->name('index');
                Route::post('/',             [SupplierController::class, 'store'])->name('store');
                Route::get('/{supplier}',    [SupplierController::class, 'show'])->name('show');
                Route::put('/{supplier}',    [SupplierController::class, 'update'])->name('update');
                Route::delete('/{supplier}', [SupplierController::class, 'destroy'])->name('destroy');
            });

            // Products
            Route::prefix('products')->name('products.')->group(function () {
                Route::get('pricelist',         [ProductsController::class, 'pricelist'])->name('pricelist');
                Route::get('low-stock',         [ProductsController::class, 'lowStock'])->name('low-stock');
                Route::get('barcode/{code}',    [ProductsController::class, 'findByBarcode'])
                    ->where('code', '[A-Za-z0-9\-\._]+')
                    ->name('barcode');
                Route::get('/',                 [ProductsController::class, 'index'])->name('index');
                Route::post('/',                [ProductsController::class, 'store'])->name('store');
                Route::get('/{product}',        [ProductsController::class, 'show'])->name('show');
                Route::put('/{product}',        [ProductsController::class, 'update'])->name('update');
                Route::delete('/{product}',     [ProductsController::class, 'destroy'])->name('destroy');
            });

            // Sales / POS
            Route::prefix('sales')->name('sales.')->group(function () {
                Route::get('/',                  [SaleController::class, 'index'])->name('index');
                Route::post('/',                 [SaleController::class, 'store'])->name('store');
                Route::get('/{sale}',            [SaleController::class, 'show'])->name('show');
                Route::post('/{sale}/suspend',   [SaleController::class, 'suspend'])->name('suspend');
                Route::post('/{sale}/resume',    [SaleController::class, 'resume'])->name('resume');
                Route::post('/{sale}/commit',    [SaleController::class, 'commit'])->name('commit');
                Route::post('/{sale}/refund',    [SaleController::class, 'refund'])->name('refund');
                Route::get('/{sale}/receipt',    [SaleController::class, 'receipt'])->name('receipt');
                Route::post('/{sale}/void',      [SaleController::class, 'void'])->name('void');
            });

            // Expenses
            Route::apiResource('expenses', ExpenseController::class);

            // Inventory
            Route::prefix('inventory')->name('inventory.')->group(function () {
                Route::get('/',        [InventoryController::class, 'index'])->name('logs');
                Route::post('/adjust', [InventoryController::class, 'adjust'])->name('adjust');
            });

            // Audit logs (manager+)
            Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

            // Dashboard & Reports
            Route::prefix('dashboard')->name('dashboard.')->group(function () {
                Route::get('summary',         [DashboardController::class, 'summary'])->name('summary');
                Route::get('sales-report',    [DashboardController::class, 'salesReport'])->name('sales-report');
                Route::get('expense-report',  [DashboardController::class, 'expenseReport'])->name('expense-report');
                Route::get('top-products',    [DashboardController::class, 'topProducts'])->name('top-products');
            });

            // ─── Eatery / Table-service POS ────────────────────────────────
            Route::middleware('module:eatery')->prefix('eatery')->name('eatery.')->group(function () {
                // Restaurant tables
                Route::prefix('tables')->name('tables.')->group(function () {
                    Route::get('/',                     [RestaurantTableController::class, 'index'])->name('index');
                    Route::post('/',                    [RestaurantTableController::class, 'store'])->name('store');
                    Route::get('/anomalies',            [RestaurantTableController::class, 'anomalies'])->name('anomalies');
                    Route::post('/sync-statuses',       [RestaurantTableController::class, 'syncStatuses'])->name('sync-statuses');
                    Route::get('/{table}',              [RestaurantTableController::class, 'show'])->name('show');
                    Route::put('/{table}',              [RestaurantTableController::class, 'update'])->name('update');
                    Route::delete('/{table}',           [RestaurantTableController::class, 'destroy'])->name('destroy');
                    Route::get('/{table}/active-order', [OrderController::class, 'activeForTable'])->name('active-order');
                });

                // Menu items
                Route::prefix('menu')->name('menu.')->group(function () {
                    Route::get('/',          [MenuItemController::class, 'index'])->name('index');
                    Route::post('/',         [MenuItemController::class, 'store'])->name('store');
                    Route::get('/{menu}',    [MenuItemController::class, 'show'])->name('show');
                    Route::put('/{menu}',    [MenuItemController::class, 'update'])->name('update');
                    Route::delete('/{menu}', [MenuItemController::class, 'destroy'])->name('destroy');
                });

                // Orders
                Route::prefix('orders')->name('orders.')->group(function () {
                    Route::get('/',                 [OrderController::class, 'index'])->name('index');
                    Route::post('/',                [OrderController::class, 'store'])->name('store');
                    Route::get('/{order}',          [OrderController::class, 'show'])->name('show');
                    Route::post('/{order}/items',   [OrderController::class, 'addItems'])->name('add-items');
                    Route::post('/{order}/cancel',  [OrderController::class, 'cancel'])->name('cancel');
                });

                // Payments
                Route::prefix('payments')->name('payments.')->group(function () {
                    Route::get('/',           [PaymentController::class, 'index'])->name('index');
                    Route::post('/',          [PaymentController::class, 'store'])->name('store');
                    Route::get('/{payment}',  [PaymentController::class, 'show'])->name('show');
                });

                // Eatery dashboard & reports
                Route::prefix('dashboard')->name('dashboard.')->group(function () {
                    Route::get('summary', [EateryDashboardController::class, 'summary'])->name('summary');
                });
                Route::prefix('reports')->name('reports.')->group(function () {
                    Route::get('daily',   [EateryDashboardController::class, 'daily'])->name('daily');
                    Route::get('monthly', [EateryDashboardController::class, 'monthly'])->name('monthly');
                });
            });
        });

        // ─── Super Admin Routes ───────────────────────────────────────────────
        Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {
            Route::get('tenants',                         [AdminController::class, 'tenants'])->name('tenants');
            Route::get('tenants/{tenant}',                [AdminController::class, 'showTenant'])->name('tenants.show');
            Route::post('tenants/{tenant}/verify',        [AdminController::class, 'verifyTenant'])->name('tenants.verify');
            Route::post('tenants/{tenant}/suspend',       [AdminController::class, 'suspendTenant'])->name('tenants.suspend');
            Route::post('tenants/{tenant}/subscription',  [AdminController::class, 'assignSubscription'])->name('tenants.subscription');

            Route::put('tenants/{tenant}/modules', [AdminController::class, 'updateModules'])->name('tenants.modules');

            // Module registry (super-admin / developer-only)
            Route::get('modules',             [AdminModuleController::class, 'index'])->name('modules.index');
            Route::post('modules',            [AdminModuleController::class, 'store'])->name('modules.store');
            Route::get('modules/{module}',    [AdminModuleController::class, 'show'])->name('modules.show');
            Route::put('modules/{module}',    [AdminModuleController::class, 'update'])->name('modules.update');
            Route::delete('modules/{module}', [AdminModuleController::class, 'destroy'])->name('modules.destroy');

            Route::get('plans',            [AdminController::class, 'plans'])->name('plans');
            Route::post('plans',           [AdminController::class, 'createPlan'])->name('plans.create');
            Route::put('plans/{plan}',     [AdminController::class, 'updatePlan'])->name('plans.update');

            Route::get('invoices',                  [AdminController::class, 'invoices'])->name('invoices');
            Route::post('invoices/{invoice}/verify',[AdminController::class, 'verifyInvoice'])->name('invoices.verify');
            Route::post('invoices/{invoice}/reject',[AdminController::class, 'rejectInvoice'])->name('invoices.reject');
        });
    });
});

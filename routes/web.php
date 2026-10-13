<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\JobCardController;
use App\Http\Controllers\WarrantyController;
use App\Http\Controllers\DuesController;
use App\Http\Controllers\RentalController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TechnicianPortalController;
use App\Http\Controllers\TechnicianController;

/*
|--------------------------------------------------------------------------
| Web Routes - Enterprises CRM (SaaS Architecture)
|--------------------------------------------------------------------------
*/

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/captcha', [AuthController::class, 'getCaptcha'])->name('captcha.get');
Route::get('/captcha-alt', [AuthController::class, 'getCaptcha'])->name('captcha');

// Technician legacy portal shortcuts
Route::get('/technician', fn() => redirect()->route('tech_portal.dashboard'));
Route::get('/technician/login', fn() => redirect()->route('tech_portal.login'));

// Authenticated CRM Routes
Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/overview', [DashboardController::class, 'overview'])->name('dashboard.overview');
    Route::get('/inquiries', fn() => redirect()->route('customers.index'))->name('inquiries.index');

    // Global Command Palette Search (Ctrl+K)
    Route::get('/search/global', [SearchController::class, 'globalSearch'])->name('search.global');

    // Customers
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::get('/customers/export/csv', [CustomerController::class, 'exportCsv'])->name('customers.export');
    Route::post('/customers/import/analyze', [CustomerController::class, 'analyzeImport'])->name('customers.import.analyze');
    Route::post('/customers/import/commit', [CustomerController::class, 'commitImport'])->name('customers.import.commit');
    Route::get('/customers/import/sample', [CustomerController::class, 'downloadSampleImport'])->name('customers.import.sample');
    Route::get('/customers/{id}', [CustomerController::class, 'show'])->name('customers.show');
    Route::put('/customers/{id}', [CustomerController::class, 'update'])->name('customers.update');

    // Customer Assets
    Route::get('/customers/{customerId}/assets', [ServiceController::class, 'customerAssets'])->name('customers.assets.list');
    Route::post('/customers/{customerId}/assets', [AssetController::class, 'store'])->name('customers.assets.store');
    Route::post('/assets', [AssetController::class, 'store'])->name('assets.store');

    // Invoices
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::get('/invoices/export/csv', [InvoiceController::class, 'exportCsv'])->name('invoices.export');
    Route::get('/invoices/{id}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::post('/invoices/{id}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel');
    Route::post('/invoices/{id}/due-mail', [InvoiceController::class, 'sendDueMail'])->name('invoices.due_mail');
    Route::post('/invoices/{id}/payments', [PaymentController::class, 'store'])->name('invoices.payments.store');

    // Sales Orders
    Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
    Route::post('/sales', [SaleController::class, 'store'])->name('sales.store');
    Route::get('/sales/{id}', [SaleController::class, 'show'])->name('sales.show');

    // Payments & Collections
    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::get('/payments/{id}', [PaymentController::class, 'show'])->name('payments.show');
    Route::post('/payments/{id}/cancel', [PaymentController::class, 'cancel'])->name('payments.cancel');

    // Inventory & Spare Parts
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store');
    Route::post('/inventory/purchases', [InventoryController::class, 'recordPurchase'])->name('inventory.purchases');
    Route::post('/inventory/sales', [InventoryController::class, 'recordSale'])->name('inventory.sales');

    // Services & Field Operations
    Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
    Route::get('/services/calendar-heatmap', [ServiceController::class, 'calendarHeatmap'])->name('services.calendar_heatmap');
    Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
    Route::get('/services/{id}', [ServiceController::class, 'show'])->name('services.show');
    Route::post('/services/{id}/assign', [ServiceController::class, 'assignTechnician'])->name('services.assign');
    Route::post('/services/{id}/complete', [ServiceController::class, 'complete'])->name('services.complete');

    // Technicians & Field Workforce Directory
    Route::get('/technicians', [TechnicianController::class, 'index'])->name('technicians.index');
    Route::get('/technicians/map', [TechnicianController::class, 'map'])->name('technicians.map');
    Route::post('/technicians', [TechnicianController::class, 'store'])->name('technicians.store');
    Route::get('/technicians/{id}', [TechnicianController::class, 'show'])->name('technicians.show');
    Route::put('/technicians/{id}', [TechnicianController::class, 'update'])->name('technicians.update');
    Route::delete('/technicians/{id}', [TechnicianController::class, 'destroy'])->name('technicians.destroy');
    Route::post('/technicians/{id}/status', [TechnicianController::class, 'updateStatus'])->name('technicians.status');
    Route::post('/technicians/{id}/portal-access', [TechnicianController::class, 'updatePortalAccess'])->name('technicians.portal_access');
    Route::post('/technicians/{id}/reset-pin', [TechnicianController::class, 'resetPin'])->name('technicians.reset_pin');
    Route::get('/api/v1/technicians/{id}/360', [TechnicianController::class, 'api360Profile'])->name('technicians.api.360');

    // Live Maps Integration Endpoints (Admin Dispatcher)
    Route::get('/maps/admin/config', [TechnicianController::class, 'mapsConfig'])->name('maps.admin.config');
    Route::get('/maps/admin/live-technicians', [TechnicianController::class, 'liveTechnicians'])->name('maps.admin.live');
    Route::post('/maps/admin/recalculate-route', [TechnicianController::class, 'recalculateRoute'])->name('maps.admin.recalculate');

    // Job Cards
    Route::get('/job-cards', [JobCardController::class, 'index'])->name('job_cards.index');
    Route::get('/job-cards/{id}', [JobCardController::class, 'show'])->name('job_cards.show');

    // Warranties & AMC
    Route::get('/warranties', [WarrantyController::class, 'index'])->name('warranties.index');
    Route::get('/warranties/{id}', [WarrantyController::class, 'show'])->name('warranties.show');
    Route::post('/warranties/{id}/event', [WarrantyController::class, 'recordEvent'])->name('warranties.event');

    // Dues & Follow-up Ledger
    Route::get('/dues', [DuesController::class, 'index'])->name('dues.index');

    // Rentals
    Route::get('/rentals', [RentalController::class, 'index'])->name('rentals.index');
    Route::post('/rentals', [RentalController::class, 'store'])->name('rentals.store');

    // Executive Reports & Analytics
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    // System Settings & Audit Logs
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
    Route::post('/settings/sequences', [SettingController::class, 'updateSequences'])->name('settings.sequences.update');
    Route::get('/settings/clean-data/preview', [SettingController::class, 'cleanDataPreview'])->name('settings.clean_data.preview');
    Route::post('/settings/clean-data/execute', [SettingController::class, 'executeCleanData'])->name('settings.clean_data.execute');
});

// Field Technician Mobile Portal Routes
Route::prefix('tech-portal')->group(function () {
    Route::get('/login', [TechnicianPortalController::class, 'login'])->name('tech_portal.login');
    Route::get('/login-alias', [TechnicianPortalController::class, 'login'])->name('technician.login');
    Route::post('/login', [TechnicianPortalController::class, 'authenticate'])->name('tech_portal.authenticate');
    Route::get('/logout', [TechnicianPortalController::class, 'logout'])->name('tech_portal.logout');

    Route::get('/dashboard', [TechnicianPortalController::class, 'dashboard'])->name('tech_portal.dashboard');
    Route::get('/services/{id}', [TechnicianPortalController::class, 'serviceDetail'])->name('tech_portal.service');
    Route::get('/services/{id}/navigate', [TechnicianPortalController::class, 'navigationScreen'])->name('tech_portal.navigation');
    Route::post('/services/{id}/navigate', [TechnicianPortalController::class, 'startNavigation'])->name('tech_portal.navigate.start');
    Route::post('/services/{id}/status', [TechnicianPortalController::class, 'updateStatus'])->name('tech_portal.status');
    Route::post('/services/{id}/complete', [TechnicianPortalController::class, 'completeService'])->name('tech_portal.complete');
    Route::post('/stop-navigation', [TechnicianPortalController::class, 'stopNavigation'])->name('tech_portal.navigate.stop');
    Route::post('/gps', [TechnicianPortalController::class, 'updateGps'])->name('tech_portal.gps');

    // Legacy API aliases for mobile client compatibility
    Route::post('/maps/technician/navigate', [TechnicianPortalController::class, 'apiStartNavigate']);
    Route::post('/maps/technician/location', [TechnicianPortalController::class, 'apiLocationPing']);
});

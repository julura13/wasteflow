<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Settings\ClassificationController;
use App\Http\Controllers\Settings\ContainerOptionController;
use App\Http\Controllers\Settings\FacilityController;
use App\Http\Controllers\Settings\GradeController;
use App\Http\Controllers\Settings\WasteStreamController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'permission:view-dashboard'])->name('dashboard');

Route::get('/dashboard/branches', [App\Http\Controllers\DashboardController::class, 'getBranches'])
    ->middleware(['auth', 'verified', 'permission:view-dashboard'])->name('dashboard.branches');

Route::get('/dashboard/sites', [App\Http\Controllers\DashboardController::class, 'getSites'])
    ->middleware(['auth', 'verified', 'permission:view-dashboard'])->name('dashboard.sites');

Route::get('/dashboard/grade-month-detail', [App\Http\Controllers\DashboardController::class, 'getGradeMonthDailyDetail'])
    ->middleware(['auth', 'verified', 'permission:view-dashboard'])->name('dashboard.grade-month-detail');

Route::get('/dashboard/orders-for-day', [App\Http\Controllers\DashboardController::class, 'getOrdersForDay'])
    ->middleware(['auth', 'verified', 'permission:view-dashboard'])->name('dashboard.orders-for-day');

Route::get('/clients', function () {
    return Inertia::render('Clients/Index');
})->middleware(['auth', 'verified', 'permission:manage-clients'])->name('clients');

// Resource routes for CRUD operations
Route::resource('companies', App\Http\Controllers\CompanyController::class)
    ->middleware(['auth', 'verified', 'permission:manage-clients']);
Route::resource('branches', App\Http\Controllers\BranchController::class)
    ->middleware(['auth', 'verified', 'permission:manage-clients']);
Route::resource('collection-points', App\Http\Controllers\SiteController::class)
    ->middleware(['auth', 'verified', 'permission:manage-clients']);

// Orders routes - require any order-related permission
$ordersPermission = 'manage-waste-collections|orders-view|orders-create|orders-schedule|orders-generate-consolidated|orders-status-documents-required|orders-status-weight-required|orders-capture-documents|orders-capture-weights|orders-finalize';
Route::get('orders/service-providers-by-date', [App\Http\Controllers\OrderController::class, 'getServiceProvidersByDate'])
    ->middleware(['auth', 'verified', "permission:{$ordersPermission}"])->name('orders.service-providers-by-date');
Route::get('orders/check-slip-number', [App\Http\Controllers\OrderController::class, 'checkSlipNumber'])
    ->middleware(['auth', 'verified', "permission:{$ordersPermission}"])->name('orders.check-slip-number');
Route::get('orders/consolidated-pdf', [App\Http\Controllers\OrderController::class, 'downloadConsolidatedPDF'])
    ->middleware(['auth', 'verified', "permission:{$ordersPermission}"])->name('orders.consolidated-pdf');
Route::get('orders/{order}/finalize', [App\Http\Controllers\OrderController::class, 'finalizeForm'])
    ->middleware(['auth', 'verified', "permission:{$ordersPermission}"])->name('orders.finalize');
Route::post('orders/{order}/save-weights', [App\Http\Controllers\OrderController::class, 'saveWeights'])
    ->middleware(['auth', 'verified', "permission:{$ordersPermission}"])->name('orders.save-weights');
Route::post('orders/{order}/finalize', [App\Http\Controllers\OrderController::class, 'finalize'])
    ->middleware(['auth', 'verified', "permission:{$ordersPermission}"])->name('orders.finalize.store');
Route::post('orders/{order}/update-status', [App\Http\Controllers\OrderController::class, 'updateStatus'])
    ->middleware(['auth', 'verified', "permission:{$ordersPermission}"])->name('orders.update-status');
Route::get('orders/{order}/download-pdf', [App\Http\Controllers\OrderController::class, 'downloadPDF'])
    ->middleware(['auth', 'verified', "permission:{$ordersPermission}"])->name('orders.download-pdf');
Route::get('orders/seeder/index', [App\Http\Controllers\OrderSeederController::class, 'index'])
    ->middleware(['auth', 'verified', "permission:{$ordersPermission}"])->name('orders.seeder.index');
Route::post('orders/seeder/generate', [App\Http\Controllers\OrderSeederController::class, 'store'])
    ->middleware(['auth', 'verified', "permission:{$ordersPermission}"])->name('orders.seeder.generate');
Route::resource('orders', App\Http\Controllers\OrderController::class)
    ->except(['edit', 'update'])
    ->middleware(['auth', 'verified', "permission:{$ordersPermission}"]);
Route::resource('waste-types', App\Http\Controllers\WasteTypeController::class)
    ->middleware(['auth', 'verified', 'permission:manage-services']);
Route::resource('service-providers', App\Http\Controllers\ServiceProviderController::class)
    ->middleware(['auth', 'verified', 'permission:manage-services']);
Route::patch('materials/{material}/rebate-rate', [App\Http\Controllers\MaterialController::class, 'updateRebateRate'])
    ->middleware(['auth', 'verified', 'permission:manage-services'])->name('materials.update-rebate-rate');
Route::patch('materials/{material}/rebate-share', [App\Http\Controllers\MaterialController::class, 'updateRebateShare'])
    ->middleware(['auth', 'verified', 'permission:manage-services'])->name('materials.update-rebate-share');
Route::resource('materials', App\Http\Controllers\MaterialController::class)
    ->middleware(['auth', 'verified', 'permission:manage-services']);

// Media routes (used in orders – same permission as orders)
Route::middleware(['auth', 'verified', "permission:{$ordersPermission}"])->prefix('media')->name('media.')->group(function () {
    Route::post('/upload', [App\Http\Controllers\MediaController::class, 'upload'])->name('upload');
    Route::get('/{media}/download', [App\Http\Controllers\MediaController::class, 'download'])->name('download');
    Route::delete('/{media}', [App\Http\Controllers\MediaController::class, 'destroy'])->name('destroy');
});

Route::middleware(['auth', 'verified', 'permission:view-reports'])->prefix('reports')->name('reports.')->group(function () {
    Route::get('/', function () {
        return Inertia::render('Reports/Index');
    })->name('index');
    Route::get('/rebate-tracker', [App\Http\Controllers\OrderController::class, 'rebateTracker'])->name('rebate-tracker');
    Route::get('/rebate-tracker/pdf', [App\Http\Controllers\OrderController::class, 'rebateTrackerPdf'])->name('rebate-tracker-pdf');
    Route::get('/average-weight-wheelie-bins', [App\Http\Controllers\OrderController::class, 'getAverageWeightForWheelieBins'])->name('average-weight-wheelie-bins');
    Route::get('/waste-management', [App\Http\Controllers\ReportController::class, 'wasteManagement'])->name('waste-management');
    Route::get('/waste-management/pdf', [App\Http\Controllers\ReportController::class, 'wasteManagementPdf'])->name('waste-management-pdf');
    Route::get('/waste-management/summary', [App\Http\Controllers\ReportController::class, 'wasteManagementSummary'])->name('waste-management-summary');
    
    // API endpoints for cascading dropdowns
    Route::get('/waste-management/branches', [App\Http\Controllers\ReportController::class, 'getBranches'])->name('waste-management-branches');
    Route::get('/waste-management/sites', [App\Http\Controllers\ReportController::class, 'getSites'])->name('waste-management-sites');
});

// Roles and permissions management
Route::middleware(['auth', 'verified', 'permission:manage-roles'])->prefix('roles')->name('roles.')->group(function () {
    Route::get('/', [App\Http\Controllers\RoleController::class, 'index'])->name('index');
    Route::get('/create', [App\Http\Controllers\RoleController::class, 'create'])->name('create');
    Route::post('/', [App\Http\Controllers\RoleController::class, 'store'])->name('store');
    Route::get('/{role}/edit', [App\Http\Controllers\RoleController::class, 'edit'])->name('edit');
    Route::put('/{role}', [App\Http\Controllers\RoleController::class, 'update'])->name('update');
});

// User management (WasteFlow staff – roles and permissions)
Route::middleware(['auth', 'verified', 'permission:manage-users'])->prefix('users')->name('users.')->group(function () {
    Route::get('/', [App\Http\Controllers\UserController::class, 'index'])->name('index');
    Route::get('/create', [App\Http\Controllers\UserController::class, 'create'])->name('create');
    Route::post('/', [App\Http\Controllers\UserController::class, 'store'])->name('store');
    Route::get('/{user}/edit', [App\Http\Controllers\UserController::class, 'edit'])->name('edit');
    Route::put('/{user}', [App\Http\Controllers\UserController::class, 'update'])->name('update');
});

Route::middleware(['auth', 'verified', 'permission:manage-settings'])->prefix('settings')->name('settings.')->group(function () {
    Route::get('/', function () {
        return Inertia::render('Settings/Index');
    })->name('index');

    Route::resource('waste-streams', WasteStreamController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('grades', GradeController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('container-options', ContainerOptionController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('classifications', ClassificationController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('facilities', FacilityController::class)->only(['index', 'store', 'update', 'destroy']);
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/avatar', [ProfileController::class, 'uploadAvatar'])->name('profile.avatar.upload');
    Route::delete('/profile/avatar', [ProfileController::class, 'deleteAvatar'])->name('profile.avatar.delete');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

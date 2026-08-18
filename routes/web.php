<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\ClientHubAdvertController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\GlobalSearchController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderSeederController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecurringOrderController;
use App\Http\Controllers\ReleaseNoteController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ServiceProviderController;
use App\Http\Controllers\Settings\ClassificationController;
use App\Http\Controllers\Settings\ContainerOptionController;
use App\Http\Controllers\Settings\FacilityController;
use App\Http\Controllers\Settings\GradeController;
use App\Http\Controllers\Settings\RecoveryRatingController;
use App\Http\Controllers\Settings\WasteStreamController;
use App\Http\Controllers\SheqComplianceController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WasteTypeController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'permission:view-dashboard'])->name('dashboard');

Route::get('/dashboard/branches', [DashboardController::class, 'getBranches'])
    ->middleware(['auth', 'verified', 'permission:view-dashboard'])->name('dashboard.branches');

Route::get('/dashboard/sites', [DashboardController::class, 'getSites'])
    ->middleware(['auth', 'verified', 'permission:view-dashboard'])->name('dashboard.sites');

Route::get('/dashboard/grade-month-detail', [DashboardController::class, 'getGradeMonthDailyDetail'])
    ->middleware(['auth', 'verified', 'permission:view-dashboard'])->name('dashboard.grade-month-detail');

Route::get('/dashboard/orders-for-day', [DashboardController::class, 'getOrdersForDay'])
    ->middleware(['auth', 'verified', 'permission:view-dashboard'])->name('dashboard.orders-for-day');

Route::get('/dashboard/container-month-detail', [DashboardController::class, 'getContainerMonthDailyDetail'])
    ->middleware(['auth', 'verified', 'permission:view-dashboard'])->name('dashboard.container-month-detail');

Route::get('/dashboard/orders-for-day-by-container', [DashboardController::class, 'getOrdersForDayByContainer'])
    ->middleware(['auth', 'verified', 'permission:view-dashboard'])->name('dashboard.orders-for-day-by-container');

Route::get('/activity-log', [ActivityLogController::class, 'index'])
    ->middleware(['auth', 'verified', 'permission:view-activity-log'])->name('activity-log.index');

Route::get('/clients', function () {
    return Inertia::render('Clients/Index');
})->middleware(['auth', 'verified', 'permission:manage-clients'])->name('clients');

// Resource routes for CRUD operations
Route::resource('companies', CompanyController::class)
    ->middleware(['auth', 'verified', 'permission:manage-clients']);
Route::resource('branches', BranchController::class)
    ->middleware(['auth', 'verified', 'permission:manage-clients']);
Route::resource('collection-points', SiteController::class)
    ->middleware(['auth', 'verified', 'permission:manage-clients']);

// Orders routes - require any order-related permission
$ordersPermission = 'manage-waste-collections|orders-view|orders-create|orders-schedule|orders-generate-consolidated|orders-status-documents-required|orders-status-weight-required|orders-capture-documents|orders-capture-weights|orders-finalize';
Route::get('orders/service-providers-by-date', [OrderController::class, 'getServiceProvidersByDate'])
    ->middleware(['auth', 'verified', "permission:{$ordersPermission}"])->name('orders.service-providers-by-date');
Route::get('orders/check-slip-number', [OrderController::class, 'checkSlipNumber'])
    ->middleware(['auth', 'verified', "permission:{$ordersPermission}"])->name('orders.check-slip-number');
Route::get('orders/consolidated-pdf', [OrderController::class, 'downloadConsolidatedPDF'])
    ->middleware(['auth', 'verified', "permission:{$ordersPermission}"])->name('orders.consolidated-pdf');
Route::get('orders/{order}/finalize', [OrderController::class, 'finalizeForm'])
    ->middleware(['auth', 'verified', "permission:{$ordersPermission}"])->name('orders.finalize');
Route::post('orders/{order}/save-weights', [OrderController::class, 'saveWeights'])
    ->middleware(['auth', 'verified', "permission:{$ordersPermission}"])->name('orders.save-weights');
Route::post('orders/{order}/finalize', [OrderController::class, 'finalize'])
    ->middleware(['auth', 'verified', "permission:{$ordersPermission}"])->name('orders.finalize.store');
Route::post('orders/{order}/update-status', [OrderController::class, 'updateStatus'])
    ->middleware(['auth', 'verified', "permission:{$ordersPermission}"])->name('orders.update-status');
Route::get('orders/{order}/download-pdf', [OrderController::class, 'downloadPDF'])
    ->middleware(['auth', 'verified', "permission:{$ordersPermission}"])->name('orders.download-pdf');
Route::get('orders/{order}/edit-collection-date', [OrderController::class, 'editCollectionDate'])
    ->middleware(['auth', 'verified', "permission:{$ordersPermission}"])->name('orders.edit-collection-date');
Route::put('orders/{order}/collection-date', [OrderController::class, 'updateCollectionDate'])
    ->middleware(['auth', 'verified', "permission:{$ordersPermission}"])->name('orders.update-collection-date');
Route::post('orders/{order}/delete', [OrderController::class, 'deleteOrder'])
    ->middleware(['auth', 'verified', "permission:{$ordersPermission}"])->name('orders.delete');
Route::post('orders/export', [OrderController::class, 'requestOrderIndexExport'])
    ->middleware(['auth', 'verified', "permission:{$ordersPermission}"])->name('orders.export.request');
Route::get('orders/export/{uuid}/status', [OrderController::class, 'orderIndexExportStatus'])
    ->middleware(['auth', 'verified', "permission:{$ordersPermission}"])->name('orders.export.status');
Route::get('orders/export/{uuid}/download', [OrderController::class, 'downloadOrderIndexExport'])
    ->middleware(['auth', 'verified', "permission:{$ordersPermission}"])->name('orders.export.download');
Route::get('orders/seeder/index', [OrderSeederController::class, 'index'])
    ->middleware(['auth', 'verified', "permission:{$ordersPermission}"])->name('orders.seeder.index');
Route::post('orders/seeder/generate', [OrderSeederController::class, 'store'])
    ->middleware(['auth', 'verified', "permission:{$ordersPermission}"])->name('orders.seeder.generate');
Route::resource('orders', OrderController::class)
    ->middleware(['auth', 'verified', "permission:{$ordersPermission}"]);

// Recurring orders
Route::middleware(['auth', 'verified', 'permission:manage-recurring-orders'])->group(function () {
    Route::resource('recurring-orders', RecurringOrderController::class)
        ->except(['show']);
    Route::post('recurring-orders/{id}/restore', [RecurringOrderController::class, 'restore'])
        ->name('recurring-orders.restore');
});

Route::resource('waste-types', WasteTypeController::class)
    ->middleware(['auth', 'verified', 'permission:manage-services']);
Route::resource('service-providers', ServiceProviderController::class)
    ->middleware(['auth', 'verified', 'permission:manage-services']);
Route::patch('materials/{material}/rebate-rate', [MaterialController::class, 'updateRebateRate'])
    ->middleware(['auth', 'verified', 'permission:manage-services'])->name('materials.update-rebate-rate');
Route::patch('materials/{material}/rebate-share', [MaterialController::class, 'updateRebateShare'])
    ->middleware(['auth', 'verified', 'permission:manage-services'])->name('materials.update-rebate-share');
Route::get('materials/export/pdf', [MaterialController::class, 'exportPdf'])
    ->middleware(['auth', 'verified', 'permission:manage-services'])->name('materials.export.pdf');
Route::resource('materials', MaterialController::class)
    ->middleware(['auth', 'verified', 'permission:manage-services']);

// Media routes (used in orders – same permission as orders)
Route::middleware(['auth', 'verified', "permission:{$ordersPermission}"])->prefix('media')->name('media.')->group(function () {
    Route::post('/upload', [MediaController::class, 'upload'])->name('upload');
    Route::get('/{media}/download', [MediaController::class, 'download'])->name('download');
    Route::delete('/{media}', [MediaController::class, 'destroy'])->name('destroy');
});

// Documents (viewable by all authenticated users; upload/edit/delete restricted to manage-documents)
Route::middleware(['auth', 'verified'])->prefix('documents')->name('documents.')->group(function () {
    Route::get('/', [DocumentController::class, 'index'])->name('index');
    Route::get('/{document}/view', [DocumentController::class, 'view'])->name('view');
    Route::get('/{document}/download', [DocumentController::class, 'download'])->name('download');

    Route::middleware(['permission:manage-documents'])->group(function () {
        Route::post('/', [DocumentController::class, 'store'])->name('store');
        Route::put('/{document}', [DocumentController::class, 'update'])->name('update');
        Route::delete('/{document}', [DocumentController::class, 'destroy'])->name('destroy');
    });
});

// SHEQ Compliance / HSE File documents, stored as standalone `media` rows with
// collection=sheq_compliance. Visibility is folder-level: the whole section requires
// view-sheq-compliance, and every document within it is visible to anyone with that
// permission (no more per-document company restriction).
Route::middleware(['auth', 'verified', 'permission:view-sheq-compliance'])->prefix('sheq-compliance')->name('sheq-compliance.')->group(function () {
    Route::get('/', [SheqComplianceController::class, 'index'])->name('index');
    Route::get('/{sheqCompliance}/view', [SheqComplianceController::class, 'view'])->name('view');
    Route::get('/{sheqCompliance}/download', [SheqComplianceController::class, 'download'])->name('download');

    Route::middleware(['permission:manage-documents'])->group(function () {
        Route::post('/', [SheqComplianceController::class, 'store'])->name('store');
        Route::put('/{sheqCompliance}', [SheqComplianceController::class, 'update'])->name('update');
        Route::delete('/{sheqCompliance}', [SheqComplianceController::class, 'destroy'])->name('destroy');
        Route::post('/{sheqCompliance}/move-up', [SheqComplianceController::class, 'moveUp'])->name('move-up');
        Route::post('/{sheqCompliance}/move-down', [SheqComplianceController::class, 'moveDown'])->name('move-down');
    });
});

// Client Hub adverts (WCP-39): admin-managed popup announcements shown to client-role users on
// login. dismiss/read are two independent flags - see ClientHubAdvertController for why.
// index renders the admin management list or a client's own read-only advert list, branching
// on role inside the controller (same pattern as SheqComplianceController/DocumentController) -
// so clients have a permanent place to find an advert again after dismissing its popup.
Route::middleware(['auth', 'verified'])->prefix('client-hub')->name('client-hub.')->group(function () {
    Route::get('/', [ClientHubAdvertController::class, 'index'])->name('index');
    Route::get('/{clientHubAdvert}/view', [ClientHubAdvertController::class, 'view'])->name('view');

    Route::middleware(['role:client'])->group(function () {
        Route::post('/read-all', [ClientHubAdvertController::class, 'readAll'])->name('read-all');
        Route::post('/{clientHubAdvert}/dismiss', [ClientHubAdvertController::class, 'dismiss'])->name('dismiss');
        Route::post('/{clientHubAdvert}/read', [ClientHubAdvertController::class, 'read'])->name('read');
    });

    Route::middleware(['role:admin'])->group(function () {
        Route::post('/', [ClientHubAdvertController::class, 'store'])->name('store');
        Route::put('/{clientHubAdvert}', [ClientHubAdvertController::class, 'update'])->name('update');
        Route::delete('/{clientHubAdvert}', [ClientHubAdvertController::class, 'destroy'])->name('destroy');
    });
});

// Unauthenticated print-preview used by Browsershot — auth via short-lived cache token
Route::get('/reports/resource-intelligence/print/{token}', [ReportController::class, 'resourceIntelligencePrintPreview'])
    ->name('reports.resource-intelligence.print-preview');

Route::middleware(['auth', 'verified', 'permission:view-reports'])->prefix('reports')->name('reports.')->group(function () {
    Route::get('/', function () {
        return Inertia::render('Reports/Index');
    })->name('index');
    Route::get('/rebate-tracker', [OrderController::class, 'rebateTracker'])->name('rebate-tracker');
    Route::post('/rebate-tracker/pdf', [OrderController::class, 'requestRebateTrackerPdf'])->name('rebate-tracker-pdf.request');
    Route::get('/rebate-tracker/pdf/{uuid}/status', [OrderController::class, 'rebateTrackerPdfStatus'])->name('rebate-tracker-pdf.status');
    Route::get('/rebate-tracker/pdf/{uuid}/download', [OrderController::class, 'downloadRebateTrackerPdf'])->name('rebate-tracker-pdf.download');
    Route::get('/waste-stream-collection', [OrderController::class, 'wasteStreamCollectionReport'])->name('waste-stream-collection');
    Route::post('/waste-stream-collection/pdf', [OrderController::class, 'requestWasteStreamCollectionPdf'])->name('waste-stream-collection-pdf.request');
    Route::get('/waste-stream-collection/pdf/{uuid}/status', [OrderController::class, 'wasteStreamCollectionPdfStatus'])->name('waste-stream-collection-pdf.status');
    Route::get('/waste-stream-collection/pdf/{uuid}/download', [OrderController::class, 'downloadWasteStreamCollectionPdf'])->name('waste-stream-collection-pdf.download');
    Route::get('/average-weight-wheelie-bins', [OrderController::class, 'getAverageWeightForWheelieBins'])->name('average-weight-wheelie-bins');
    Route::get('/customer-order-frequencies/export-pdf', [ReportController::class, 'customerOrderFrequenciesExportPdf'])
        ->middleware('permission:view-reports-all')
        ->name('customer-order-frequencies.export-pdf');
    Route::get('/customer-order-frequencies/export', [ReportController::class, 'customerOrderFrequenciesExport'])
        ->middleware('permission:view-reports-all')
        ->name('customer-order-frequencies.export');
    Route::get('/customer-order-frequencies', [ReportController::class, 'customerOrderFrequencies'])
        ->middleware('permission:view-reports-all')
        ->name('customer-order-frequencies');
    Route::get('/management-report/export-pdf', [ReportController::class, 'managementReportExportPdf'])
        ->middleware('permission:view-reports-all')
        ->name('management-report.export-pdf');
    Route::get('/management-report/export', [ReportController::class, 'managementReportExport'])
        ->middleware('permission:view-reports-all')
        ->name('management-report.export');
    Route::get('/management-report', [ReportController::class, 'managementReport'])
        ->middleware('permission:view-reports-all')
        ->name('management-report');
    Route::get('/waste-management', [ReportController::class, 'wasteManagement'])->name('waste-management');
    Route::post('/waste-management/pdf/request', [ReportController::class, 'requestWasteManagementPdf'])->name('waste-management-pdf.request');
    Route::get('/waste-management/pdf/{uuid}/status', [ReportController::class, 'wasteManagementPdfStatus'])->name('waste-management-pdf.status');
    Route::get('/waste-management/pdf/{uuid}/download', [ReportController::class, 'downloadWasteManagementPdf'])->name('waste-management-pdf.download');
    Route::get('/waste-management/certificate', [ReportController::class, 'downloadClientMonthlyCertificate'])->name('waste-management-certificate.download');
    Route::get('/waste-management/summary', [ReportController::class, 'wasteManagementSummary'])->name('waste-management-summary');
    Route::get('/resource-intelligence', [ReportController::class, 'resourceIntelligenceView'])->name('resource-intelligence');
    Route::get('/carbon-calculator', [ReportController::class, 'carbonCalculator'])
        ->middleware(['permission:view-carbon-calculator'])
        ->name('carbon-calculator');
    Route::post('/carbon-calculator/calculate', [ReportController::class, 'carbonCalculatorCalculate'])
        ->middleware(['permission:view-carbon-calculator'])
        ->name('carbon-calculator.calculate');
    Route::get('/landfill-space-calculator', [ReportController::class, 'landfillSpaceCalculator'])
        ->middleware(['permission:view-landfill-space-calculator'])
        ->name('landfill-space-calculator');
    Route::post('/landfill-space-calculator/calculate', [ReportController::class, 'landfillSpaceCalculatorCalculate'])
        ->middleware(['permission:view-landfill-space-calculator'])
        ->name('landfill-space-calculator.calculate');
    Route::get('/water-calculator', [ReportController::class, 'waterCalculator'])
        ->middleware(['permission:view-water-calculator'])
        ->name('water-calculator');
    Route::post('/water-calculator/calculate', [ReportController::class, 'waterCalculatorCalculate'])
        ->middleware(['permission:view-water-calculator'])
        ->name('water-calculator.calculate');

    // API endpoints for cascading dropdowns
    Route::get('/waste-management/branches', [ReportController::class, 'getBranches'])->name('waste-management-branches');
    Route::get('/waste-management/sites', [ReportController::class, 'getSites'])->name('waste-management-sites');
});

// Roles and permissions management
Route::middleware(['auth', 'verified', 'permission:manage-roles'])->prefix('roles')->name('roles.')->group(function () {
    Route::get('/', [RoleController::class, 'index'])->name('index');
    Route::get('/create', [RoleController::class, 'create'])->name('create');
    Route::post('/', [RoleController::class, 'store'])->name('store');
    Route::get('/{role}/edit', [RoleController::class, 'edit'])->name('edit');
    Route::put('/{role}', [RoleController::class, 'update'])->name('update');
});

// User management (WasteFlow staff – roles and permissions)
Route::middleware(['auth', 'verified', 'permission:manage-users'])->prefix('users')->name('users.')->group(function () {
    Route::get('/', [UserController::class, 'index'])->name('index');
    Route::get('/create', [UserController::class, 'create'])->name('create');
    Route::post('/', [UserController::class, 'store'])->name('store');
    Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
    Route::put('/{user}', [UserController::class, 'update'])->name('update');
    Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
    Route::post('/{user}/impersonate', [UserController::class, 'impersonate'])->name('impersonate');
});

Route::middleware(['auth'])->post('/users/impersonate/leave', [UserController::class, 'leaveImpersonation'])->name('users.impersonate.leave');

Route::middleware(['auth', 'verified', 'permission:manage-settings'])->prefix('settings')->name('settings.')->group(function () {
    Route::get('/', function () {
        return Inertia::render('Settings/Index');
    })->name('index');

    Route::resource('waste-streams', WasteStreamController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('grades', GradeController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('container-options', ContainerOptionController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::patch('container-options/{containerOption}/toggle-summary', [ContainerOptionController::class, 'toggleSummary'])->name('container-options.toggle-summary');
    Route::resource('classifications', ClassificationController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('facilities', FacilityController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::get('recovery-rating', [RecoveryRatingController::class, 'index'])->name('recovery-rating.index');
    Route::put('recovery-rating', [RecoveryRatingController::class, 'update'])->name('recovery-rating.update');
});

Route::middleware('auth')->get('/search', GlobalSearchController::class)->name('search');

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::post('/release-notes/{releaseNote}/read', [ReleaseNoteController::class, 'markAsRead'])->name('release-notes.read');
    Route::post('/release-notes/read-all', [ReleaseNoteController::class, 'markAllAsRead'])->name('release-notes.read-all');
    Route::post('/notifications/{notificationId}/read', [ReleaseNoteController::class, 'markNotificationAsRead'])->name('notifications.read');
});

Route::middleware('auth')->group(function () {
    Route::get('/release-notes', [ReleaseNoteController::class, 'index'])->name('release-notes.index');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/avatar', [ProfileController::class, 'uploadAvatar'])->name('profile.avatar.upload');
    Route::delete('/profile/avatar', [ProfileController::class, 'deleteAvatar'])->name('profile.avatar.delete');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

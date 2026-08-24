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
use App\Http\Controllers\OrderExportController;
use App\Http\Controllers\OrderSeederController;
use App\Http\Controllers\OrderWorkflowController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecurringOrderController;
use App\Http\Controllers\ReleaseNoteController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Reports\CustomerOrderFrequencyReportController;
use App\Http\Controllers\Reports\EnvironmentalCalculatorController;
use App\Http\Controllers\Reports\ManagementReportController;
use App\Http\Controllers\Reports\RebateTrackerReportController;
use App\Http\Controllers\Reports\WasteStreamCollectionReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ServiceProviderController;
use App\Http\Controllers\Settings\ClassificationController;
use App\Http\Controllers\Settings\ContainerOptionController;
use App\Http\Controllers\Settings\FacilityController;
use App\Http\Controllers\Settings\GradeController;
use App\Http\Controllers\Settings\RecoveryRatingController;
use App\Http\Controllers\Settings\WasteStreamController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SheqComplianceController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WasteTypeController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});

Route::middleware(['auth', 'verified', 'permission:view-dashboard'])->prefix('dashboard')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/branches', [DashboardController::class, 'getBranches'])->name('dashboard.branches');
    Route::get('/sites', [DashboardController::class, 'getSites'])->name('dashboard.sites');
    Route::get('/grade-month-detail', [DashboardController::class, 'getGradeMonthDailyDetail'])->name('dashboard.grade-month-detail');
    Route::get('/orders-for-day', [DashboardController::class, 'getOrdersForDay'])->name('dashboard.orders-for-day');
    Route::get('/container-month-detail', [DashboardController::class, 'getContainerMonthDailyDetail'])->name('dashboard.container-month-detail');
    Route::get('/orders-for-day-by-container', [DashboardController::class, 'getOrdersForDayByContainer'])->name('dashboard.orders-for-day-by-container');
});

Route::get('/activity-log', [ActivityLogController::class, 'index'])
    ->middleware(['auth', 'verified', 'permission:view-activity-log'])->name('activity-log.index');

// Resource routes for CRUD operations
Route::middleware(['auth', 'verified', 'permission:manage-clients'])->group(function () {
    Route::resource('companies', CompanyController::class);
    Route::resource('branches', BranchController::class);
    Route::resource('collection-points', SiteController::class)
        ->parameters(['collection-points' => 'site']);
});

// Orders routes - require any order-related permission
$ordersPermission = 'manage-waste-collections|orders-view|orders-create|orders-schedule|orders-generate-consolidated|orders-status-documents-required|orders-status-weight-required|orders-capture-documents|orders-capture-weights|orders-finalize';
Route::middleware(['auth', 'verified', "permission:{$ordersPermission}"])->prefix('orders')->name('orders.')->group(function () {
    Route::get('service-providers-by-date', [OrderExportController::class, 'getServiceProvidersByDate'])->name('service-providers-by-date');
    Route::get('check-slip-number', [OrderWorkflowController::class, 'checkSlipNumber'])->name('check-slip-number');
    Route::get('consolidated-pdf', [OrderExportController::class, 'downloadConsolidatedPDF'])->name('consolidated-pdf');
    Route::get('export/{uuid}/status', [OrderExportController::class, 'orderIndexExportStatus'])->name('export.status');
    Route::get('export/{uuid}/download', [OrderExportController::class, 'downloadOrderIndexExport'])->name('export.download');
    Route::post('export', [OrderExportController::class, 'requestOrderIndexExport'])->name('export.request');
    Route::get('seeder/index', [OrderSeederController::class, 'index'])->name('seeder.index');
    Route::post('seeder/generate', [OrderSeederController::class, 'store'])->name('seeder.generate');
    Route::get('{order}/finalize', [OrderWorkflowController::class, 'finalizeForm'])->name('finalize');
    Route::post('{order}/finalize', [OrderWorkflowController::class, 'finalize'])->name('finalize.store');
    Route::post('{order}/save-weights', [OrderWorkflowController::class, 'saveWeights'])->name('save-weights');
    Route::post('{order}/update-status', [OrderWorkflowController::class, 'updateStatus'])->name('update-status');
    Route::get('{order}/download-pdf', [OrderExportController::class, 'downloadPDF'])->name('download-pdf');
    Route::get('{order}/edit-collection-date', [OrderWorkflowController::class, 'editCollectionDate'])->name('edit-collection-date');
    Route::put('{order}/collection-date', [OrderWorkflowController::class, 'updateCollectionDate'])->name('update-collection-date');
    Route::post('{order}/delete', [OrderController::class, 'deleteOrder'])->name('delete');
});
Route::resource('orders', OrderController::class)
    ->middleware(['auth', 'verified', "permission:{$ordersPermission}"]);

// Recurring orders
Route::middleware(['auth', 'verified', 'permission:manage-recurring-orders'])->group(function () {
    Route::resource('recurring-orders', RecurringOrderController::class)
        ->except(['show']);
    Route::post('recurring-orders/{id}/restore', [RecurringOrderController::class, 'restore'])
        ->name('recurring-orders.restore');
});

Route::middleware(['auth', 'verified', 'permission:manage-services'])->group(function () {
    Route::resource('waste-types', WasteTypeController::class);
    Route::resource('service-providers', ServiceProviderController::class);
    Route::patch('materials/{material}/rebate-rate', [MaterialController::class, 'updateRebateRate'])->name('materials.update-rebate-rate');
    Route::patch('materials/{material}/rebate-share', [MaterialController::class, 'updateRebateShare'])->name('materials.update-rebate-share');
    Route::get('materials/export/pdf', [MaterialController::class, 'exportPdf'])->name('materials.export.pdf');
    Route::resource('materials', MaterialController::class);
});

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
    Route::get('/', [ReportController::class, 'index'])->name('index');
    Route::get('/rebate-tracker', [RebateTrackerReportController::class, 'index'])->name('rebate-tracker');
    Route::post('/rebate-tracker/pdf', [RebateTrackerReportController::class, 'requestPdf'])->name('rebate-tracker-pdf.request');
    Route::get('/rebate-tracker/pdf/{uuid}/status', [RebateTrackerReportController::class, 'pdfStatus'])->name('rebate-tracker-pdf.status');
    Route::get('/rebate-tracker/pdf/{uuid}/download', [RebateTrackerReportController::class, 'downloadPdf'])->name('rebate-tracker-pdf.download');
    Route::get('/waste-stream-collection', [WasteStreamCollectionReportController::class, 'index'])->name('waste-stream-collection');
    Route::post('/waste-stream-collection/pdf', [WasteStreamCollectionReportController::class, 'requestPdf'])->name('waste-stream-collection-pdf.request');
    Route::get('/waste-stream-collection/pdf/{uuid}/status', [WasteStreamCollectionReportController::class, 'pdfStatus'])->name('waste-stream-collection-pdf.status');
    Route::get('/waste-stream-collection/pdf/{uuid}/download', [WasteStreamCollectionReportController::class, 'downloadPdf'])->name('waste-stream-collection-pdf.download');
    Route::get('/average-weight-wheelie-bins', [WasteStreamCollectionReportController::class, 'getAverageWeightForWheelieBins'])->name('average-weight-wheelie-bins');
    Route::get('/customer-order-frequencies/export-pdf', [CustomerOrderFrequencyReportController::class, 'exportPdf'])
        ->middleware('permission:view-reports-all')
        ->name('customer-order-frequencies.export-pdf');
    Route::get('/customer-order-frequencies/export', [CustomerOrderFrequencyReportController::class, 'export'])
        ->middleware('permission:view-reports-all')
        ->name('customer-order-frequencies.export');
    Route::get('/customer-order-frequencies', [CustomerOrderFrequencyReportController::class, 'index'])
        ->middleware('permission:view-reports-all')
        ->name('customer-order-frequencies');
    Route::get('/management-report/export-pdf', [ManagementReportController::class, 'exportPdf'])
        ->middleware('permission:view-reports-all')
        ->name('management-report.export-pdf');
    Route::get('/management-report/export', [ManagementReportController::class, 'export'])
        ->middleware('permission:view-reports-all')
        ->name('management-report.export');
    Route::get('/management-report', [ManagementReportController::class, 'index'])
        ->middleware('permission:view-reports-all')
        ->name('management-report');
    Route::get('/waste-management', [ReportController::class, 'wasteManagement'])->name('waste-management');
    Route::post('/waste-management/pdf/request', [ReportController::class, 'requestWasteManagementPdf'])->name('waste-management-pdf.request');
    Route::get('/waste-management/pdf/{uuid}/status', [ReportController::class, 'wasteManagementPdfStatus'])->name('waste-management-pdf.status');
    Route::get('/waste-management/pdf/{uuid}/download', [ReportController::class, 'downloadWasteManagementPdf'])->name('waste-management-pdf.download');
    Route::get('/waste-management/certificate', [ReportController::class, 'downloadClientMonthlyCertificate'])->name('waste-management-certificate.download');
    Route::get('/waste-management/summary', [ReportController::class, 'wasteManagementSummary'])->name('waste-management-summary');
    Route::get('/resource-intelligence', [ReportController::class, 'resourceIntelligenceView'])->name('resource-intelligence');
    Route::get('/carbon-calculator', [EnvironmentalCalculatorController::class, 'carbonCalculator'])
        ->middleware(['permission:view-carbon-calculator'])
        ->name('carbon-calculator');
    Route::post('/carbon-calculator/calculate', [EnvironmentalCalculatorController::class, 'carbonCalculatorCalculate'])
        ->middleware(['permission:view-carbon-calculator'])
        ->name('carbon-calculator.calculate');
    Route::get('/landfill-space-calculator', [EnvironmentalCalculatorController::class, 'landfillSpaceCalculator'])
        ->middleware(['permission:view-landfill-space-calculator'])
        ->name('landfill-space-calculator');
    Route::post('/landfill-space-calculator/calculate', [EnvironmentalCalculatorController::class, 'landfillSpaceCalculatorCalculate'])
        ->middleware(['permission:view-landfill-space-calculator'])
        ->name('landfill-space-calculator.calculate');
    Route::get('/water-calculator', [EnvironmentalCalculatorController::class, 'waterCalculator'])
        ->middleware(['permission:view-water-calculator'])
        ->name('water-calculator');
    Route::post('/water-calculator/calculate', [EnvironmentalCalculatorController::class, 'waterCalculatorCalculate'])
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
    Route::get('/', [SettingsController::class, 'index'])->name('index');

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

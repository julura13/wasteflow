<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Reports\RebateTrackerReportController;
use App\Http\Controllers\Reports\WasteStreamCollectionReportController;
use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Company;
use App\Models\ContainerOption;
use App\Models\Material;
use App\Models\Order;
use App\Models\ServiceProvider;
use App\Models\Site;
use App\Repositories\OrderStatusHistoryRepository;
use App\Services\CompanyUserService;
use App\Services\OrdersIndexQueryService;
use App\Services\RebateTrackerReportService;
use App\Traits\HandlesOrderEditReasonsTrait;
use App\Traits\ScopeByClientTrait;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class OrderController extends Controller
{
    use HandlesOrderEditReasonsTrait, ScopeByClientTrait;

    public function __construct(
        protected OrderStatusHistoryRepository $statusHistoryRepository,
        protected CompanyUserService $companyUserService,
        protected RebateTrackerReportService $rebateTrackerReportService,
        protected OrdersIndexQueryService $ordersIndexQueryService,
    ) {}

    public function index(Request $request)
    {
        // Status: query string wins; empty `?status=` means "all" and clears the remembered filter.
        if ($request->query->has('status')) {
            $status = $request->query('status');
            if ($status === null || $status === '') {
                session()->forget('orders_status_filter');
                $status = null;
            } else {
                session(['orders_status_filter' => $status]);
            }
        } else {
            $status = session('orders_status_filter', null);
        }

        // Order types: array of 'waste'|'recycling'. Empty or both = show all.
        $orderTypes = $request->has('order_types') ? (array) $request->input('order_types') : [];
        $orderTypes = array_values(array_unique(array_filter(array_map('strtolower', $orderTypes), fn ($t) => in_array($t, ['waste', 'recycling'], true))));

        // Service provider IDs: array of integers. Empty = show all.
        $serviceProviderIds = array_values(array_unique(array_filter(array_map('intval', (array) $request->input('service_provider_ids', [])), fn ($id) => $id > 0)));

        [$requestedCollectionFrom, $requestedCollectionTo] = $this->ordersIndexQueryService->parseRequestedCollectionDateRangeInput(
            $request->input('requested_collection_from'),
            $request->input('requested_collection_to'),
        );

        $validSortColumns = ['company'];
        $sortBy = in_array($request->input('sort_by'), $validSortColumns, true) ? $request->input('sort_by') : null;
        $sortDir = $request->input('sort_dir') === 'desc' ? 'desc' : 'asc';

        $user = auth()->user();
        $query = $this->ordersIndexQueryService->buildForUser($user, $request->input('search'), $status, $orderTypes, $requestedCollectionFrom, $requestedCollectionTo, $serviceProviderIds);
        $query = $this->ordersIndexQueryService->applyIndexOrdering($query, $sortBy, $sortDir);

        $orders = $query
            ->paginate(100)
            ->withQueryString();

        $serviceProviders = ServiceProvider::active()->get();

        $userCompanyRoles = [];
        if (! $user->isAdmin()) {
            $userCompanies = $user->companies()->get();
            foreach ($userCompanies as $company) {
                $userCompanyRoles[$company->id] = $company->pivot->role;
            }
        }

        return Inertia::render('Orders/Index', [
            'orders' => $orders,
            'filters' => [
                'search' => $request->input('search'),
                'status' => $status,
                'order_types' => $orderTypes,
                'service_provider_ids' => $serviceProviderIds,
                'requested_collection_from' => $requestedCollectionFrom,
                'requested_collection_to' => $requestedCollectionTo,
                'sort_by' => $sortBy,
                'sort_dir' => $sortDir,
            ],
            'serviceProviders' => $serviceProviders,
            'userCompanyRoles' => $userCompanyRoles,
        ]);
    }

    public function create()
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            $companies = Company::where('is_active', true)->orderBy('name')->get();
            $branches = Branch::with('company')->where('is_active', true)->orderBy('name')->get();
            $sites = Site::with(['branch.company'])->where('is_active', true)->orderBy('name')->get();
        } else {
            $companyIds = $this->companyUserService->getCompanyIdsForUser($user);
            $managerCompanyIds = collect($companyIds)->filter(function ($companyId) use ($user) {
                return $user->isManagerForCompany($companyId);
            })->toArray();

            if (empty($managerCompanyIds)) {
                abort(403, 'Only managers can create orders. Viewers can only view orders.');
            }

            $companies = Company::whereIn('id', $managerCompanyIds)->where('is_active', true)->orderBy('name')->get();
            $branches = Branch::with('company')
                ->whereHas('company', function ($q) use ($managerCompanyIds) {
                    $q->whereIn('id', $managerCompanyIds);
                })
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
            $sites = Site::with(['branch.company'])
                ->whereHas('branch.company', function ($q) use ($managerCompanyIds) {
                    $q->whereIn('id', $managerCompanyIds);
                })
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        }

        $sites->each(function ($site) {
            $site->company_name = $site->getCompanyNameAttribute();
        });

        $materials = Material::active()
            ->with([
                'grade:id,name',
                'wasteStream:id,name',
                'classification:id,name',
            ])
            ->get();
        $serviceProviders = ServiceProvider::active()->get();
        $containerOptionsWaste = ContainerOption::query()
            ->where([
                'is_active' => true,
                'order_type' => 'waste',
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        $containerOptionsRecycling = ContainerOption::query()
            ->where([
                'is_active' => true,
                'order_type' => 'recycling',
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return Inertia::render('Orders/Create', [
            'companies' => $companies,
            'branches' => $branches,
            'sites' => $sites,
            'materials' => $materials,
            'serviceProviders' => $serviceProviders,
            'containerOptionsWaste' => $containerOptionsWaste,
            'containerOptionsRecycling' => $containerOptionsRecycling,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('orders-create'), 403, 'You do not have permission to create orders.');

        $orderType = $request->input('order_type');

        $rules = [
            'company_id' => 'required|exists:companies,id',
            'branch_id' => 'required|exists:branches,id',
            'site_id' => 'nullable|exists:sites,id',
            'service_provider_id' => 'required|exists:service_providers,id',
            'order_type' => 'required|in:waste,recycling',
            'quantity_lines' => 'required|array|min:1',
            'quantity_lines.*.container_option_id' => [
                'required',
                'integer',
                Rule::exists('container_options', 'id')->where(function ($query) use ($orderType) {
                    $query->where([
                        'order_type' => $orderType,
                        'is_active' => true,
                    ]);
                }),
            ],
            'quantity_lines.*.quantity' => 'required|integer|min:1',
            'quantity_lines.*.description' => 'nullable|string|max:255',
            'requested_collection_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
        ];

        $validated = $request->validate($rules);

        $companyId = (int) $validated['company_id'];
        if (! empty($validated['site_id'])) {
            $site = Site::with('branch.company')->find($validated['site_id']);
            if ($site && $site->branch && $site->branch->company_id) {
                $companyId = (int) $site->branch->company_id;
                $validated['branch_id'] = $site->branch_id;
                $validated['company_id'] = $companyId;
            }
        }

        if (! auth()->user()->canManageOrdersForCompany($companyId)) {
            abort(403, 'Only managers can create orders for this company. Viewers can only view orders.');
        }

        $validated['created_by'] = auth()->id();
        $validated['status'] = 'pending';

        $validated['quantity_lines'] = $this->mapQuantityLinesWithContainerNames($validated['quantity_lines']);

        $totalQuantity = collect($validated['quantity_lines'])->sum('quantity');
        $validated['estimated_quantity'] = $totalQuantity;

        $validated['site_id'] = ! empty($validated['site_id']) ? $validated['site_id'] : null;

        $order = Order::create($validated);

        ActivityLog::log('order_created', "Order {$order->tracking_number} created", $order, [
            'tracking_number' => $order->tracking_number,
            'order_type' => $order->order_type,
            'requested_collection_date' => $order->requested_collection_date,
            'quantity_lines' => $order->quantity_lines,
            'estimated_quantity' => $order->estimated_quantity,
        ]);

        return redirect()->route('orders.show', $order)
            ->with('success', 'Order created successfully! Tracking number: '.$order->tracking_number);
    }

    public function show(Order $order)
    {
        $this->ensureOrderInScope($order);

        $order->load([
            'site.branch.company',
            'company',
            'branch',
            'creator',
            'serviceProvider',
            'wasteStreams.material.wasteStream',
            'wasteStreams.material.grade',
            'wasteStreams.serviceProvider',
            'supportingDocuments',
            'statusHistory.changedBy',
        ]);

        $order->append(['can_be_finalized', 'has_required_supporting_documents', 'total_rebate']);

        $user = auth()->user();
        $companyId = $order->site?->branch?->company?->id ?? $order->company_id;
        $canManageOrder = $user->isAdmin() || $user->canManageOrdersForCompany($companyId);

        return Inertia::render('Orders/Show', [
            'order' => $order,
            'canManageOrder' => $canManageOrder,
        ]);
    }

    public function edit(Order $order)
    {
        $user = auth()->user();

        if (! in_array($order->status, ['pending', 'scheduled'], true)) {
            return redirect()->route('orders.show', $order)
                ->with('error', 'Only pending or scheduled orders can be edited. For other changes, delete the order and create a new one.');
        }

        $order->load(['site.branch.company', 'company', 'branch', 'serviceProvider']);
        $companyId = $order->site?->branch?->company?->id ?? $order->company_id;

        if (! $user->canManageOrdersForCompany($companyId)) {
            abort(403, 'Only managers can edit orders for this company. Viewers can only view orders.');
        }

        $lineContainerIds = collect($order->quantity_lines ?? [])
            ->pluck('container_option_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $containerOptions = ContainerOption::query()
            ->where('order_type', $order->order_type)
            ->where(function ($query) use ($lineContainerIds) {
                $query->where('is_active', true);
                if ($lineContainerIds !== []) {
                    $query->orWhereIn('id', $lineContainerIds);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'is_active']);

        return Inertia::render('Orders/Edit', [
            'order' => $order,
            'containerOptions' => $containerOptions,
        ]);
    }

    public function update(Request $request, Order $order)
    {
        $this->ensureOrderInScope($order);

        if (! in_array($order->status, ['pending', 'scheduled'], true)) {
            return redirect()->route('orders.show', $order)
                ->with('error', 'Only pending or scheduled orders can be edited.');
        }

        $orderType = $order->order_type;
        $editReasonRules = $this->getEditReasonValidationRules();

        $rules = [
            'quantity_lines' => 'required|array|min:1',
            'quantity_lines.*.container_option_id' => [
                'required',
                'integer',
                Rule::exists('container_options', 'id')->where(function ($query) use ($orderType) {
                    $query->where('order_type', $orderType);
                }),
            ],
            'quantity_lines.*.quantity' => 'required|integer|min:1',
            'quantity_lines.*.description' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            ...$editReasonRules,
        ];

        $validated = $request->validate($rules);

        $validated['quantity_lines'] = $this->mapQuantityLinesWithContainerNames($validated['quantity_lines']);

        $validated['estimated_quantity'] = collect($validated['quantity_lines'])->sum('quantity');

        $oldQuantityLines = $order->quantity_lines;
        $oldEstimatedQuantity = $order->estimated_quantity;
        $oldNotes = $order->notes;

        $order->update([
            'quantity_lines' => $validated['quantity_lines'],
            'estimated_quantity' => $validated['estimated_quantity'],
            'notes' => $validated['notes'] ?? $order->notes,
        ]);

        $reasonLabel = $this->getEditReasonLabel($validated['reason'], $validated['reason_details'] ?? '');

        ActivityLog::log('order_updated', "Order {$order->tracking_number} details updated (quantity lines / notes) ({$reasonLabel})", $order, [
            'tracking_number' => $order->tracking_number,
            'old_quantity_lines' => $oldQuantityLines,
            'new_quantity_lines' => $validated['quantity_lines'],
            'old_estimated_quantity' => $oldEstimatedQuantity,
            'new_estimated_quantity' => $validated['estimated_quantity'],
            'old_notes' => $oldNotes,
            'new_notes' => $validated['notes'] ?? $order->notes,
            'reason' => $validated['reason'],
            'reason_details' => $validated['reason_details'] ?? null,
        ]);

        return redirect()->route('orders.show', $order)
            ->with('success', 'Order updated successfully.');
    }

    public function destroy(Order $order)
    {
        return redirect()->route('orders.index')
            ->with('error', 'Please use the delete button in the actions column and select a reason.');
    }

    public function deleteOrder(Request $request, Order $order)
    {
        $this->ensureOrderInScope($order);

        $user = auth()->user();
        $companyId = $order->site?->branch?->company?->id ?? $order->company_id;

        if (! $user->canManageOrdersForCompany($companyId)) {
            abort(403, 'Only managers can delete orders for this company. Viewers can only view orders.');
        }

        $validated = $request->validate([
            'reason' => 'required|string|in:incorrect_order,duplicate,wrong_date,wrong_site,cancelled_by_client,other',
            'reason_details' => 'nullable|string|max:1000',
        ], [
            'reason.required' => 'Please select a reason for deleting this order.',
            'reason.in' => 'Please select a valid reason.',
        ]);

        if ($validated['reason'] === 'other' && empty(trim($validated['reason_details'] ?? ''))) {
            return redirect()->back()->withErrors(['reason_details' => 'Please provide details when selecting Other.']);
        }

        $reasonLabel = $this->getDeletionReasonLabel($validated['reason'], $validated['reason_details'] ?? '');

        ActivityLog::log('order_deleted', "Order {$order->tracking_number} deleted: {$reasonLabel}", $order, [
            'order_id' => $order->id,
            'tracking_number' => $order->tracking_number,
            'reason' => $validated['reason'],
            'reason_details' => $validated['reason_details'] ?? null,
        ]);

        $order->delete();

        return redirect()->route('orders.index')
            ->with('success', 'Order deleted successfully.');
    }

    // ==========================================
    // Backward-compatible delegates to sub-controllers
    // ==========================================

    public function editCollectionDate(Order $order)
    {
        return app(OrderWorkflowController::class)->editCollectionDate($order);
    }

    public function updateCollectionDate(Request $request, Order $order)
    {
        return app(OrderWorkflowController::class)->updateCollectionDate($request, $order);
    }

    public function updateStatus(Request $request, Order $order)
    {
        return app(OrderWorkflowController::class)->updateStatus($request, $order);
    }

    public function finalizeForm(Order $order)
    {
        return app(OrderWorkflowController::class)->finalizeForm($order);
    }

    public function checkSlipNumber(Request $request)
    {
        return app(OrderWorkflowController::class)->checkSlipNumber($request);
    }

    public function saveWeights(Request $request, Order $order)
    {
        return app(OrderWorkflowController::class)->saveWeights($request, $order);
    }

    public function finalize(Request $request, Order $order)
    {
        return app(OrderWorkflowController::class)->finalize($request, $order);
    }

    public function downloadPDF(Order $order)
    {
        return app(OrderExportController::class)->downloadPDF($order);
    }

    public function requestOrderIndexExport(Request $request)
    {
        return app(OrderExportController::class)->requestOrderIndexExport($request);
    }

    public function orderIndexExportStatus(Request $request, string $uuid)
    {
        return app(OrderExportController::class)->orderIndexExportStatus($request, $uuid);
    }

    public function downloadOrderIndexExport(Request $request, string $uuid)
    {
        return app(OrderExportController::class)->downloadOrderIndexExport($request, $uuid);
    }

    public function getServiceProvidersByDate(Request $request)
    {
        return app(OrderExportController::class)->getServiceProvidersByDate($request);
    }

    public function downloadConsolidatedPDF(Request $request)
    {
        return app(OrderExportController::class)->downloadConsolidatedPDF($request);
    }

    public function rebateTracker(Request $request)
    {
        return app(RebateTrackerReportController::class)->index($request);
    }

    public function requestRebateTrackerPdf(Request $request)
    {
        return app(RebateTrackerReportController::class)->requestPdf($request);
    }

    public function rebateTrackerPdfStatus(Request $request, string $uuid)
    {
        return app(RebateTrackerReportController::class)->pdfStatus($request, $uuid);
    }

    public function downloadRebateTrackerPdf(Request $request, string $uuid)
    {
        return app(RebateTrackerReportController::class)->downloadPdf($request, $uuid);
    }

    public function wasteStreamCollectionReport(Request $request)
    {
        return app(WasteStreamCollectionReportController::class)->index($request);
    }

    public function requestWasteStreamCollectionPdf(Request $request)
    {
        return app(WasteStreamCollectionReportController::class)->requestPdf($request);
    }

    public function wasteStreamCollectionPdfStatus(Request $request, string $uuid)
    {
        return app(WasteStreamCollectionReportController::class)->pdfStatus($request, $uuid);
    }

    public function downloadWasteStreamCollectionPdf(Request $request, string $uuid)
    {
        return app(WasteStreamCollectionReportController::class)->downloadPdf($request, $uuid);
    }

    public function getAverageWeightForWheelieBins(Request $request)
    {
        return app(WasteStreamCollectionReportController::class)->getAverageWeightForWheelieBins($request);
    }
}

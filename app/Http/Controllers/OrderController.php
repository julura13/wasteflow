<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Company;
use App\Models\ContainerOption;
use App\Models\Material;
use App\Models\Order;
use App\Models\OrderWasteStream;
use App\Models\ServiceProvider;
use App\Models\Site;
use App\Repositories\OrderStatusHistoryRepository;
use App\Services\ClientMonthlySummaryService;
use App\Traits\ScopeByClientTrait;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class OrderController extends Controller
{
    use ScopeByClientTrait;

    protected $statusHistoryRepository;

    /** When user has company_id, restrict order access to that company. */
    protected function ensureOrderInScope(Order $order): void
    {
        $user = auth()->user();
        if ($user && $user->company_id && (int) $order->company_id !== (int) $user->company_id) {
            abort(403, 'You do not have access to this order.');
        }
    }

    public function __construct(OrderStatusHistoryRepository $statusHistoryRepository)
    {
        $this->statusHistoryRepository = $statusHistoryRepository;
    }

    public function index(Request $request)
    {
        // Get status from request or session, prioritize request
        $status = $request->input('status');

        // If status is provided in request, save it to session
        if ($request->has('status')) {
            if ($status) {
                session(['orders_status_filter' => $status]);
            } else {
                session()->forget('orders_status_filter');
            }
        } else {
            $status = session('orders_status_filter', null);
        }

        // Order types: array of 'waste'|'recycling'. Empty or both = show all.
        $orderTypes = $request->has('order_types') ? (array) $request->input('order_types') : [];
        $orderTypes = array_values(array_unique(array_filter(array_map('strtolower', $orderTypes), fn ($t) => in_array($t, ['waste', 'recycling'], true))));

        $query = $this->ordersIndexQuery($request->input('search'), $status, $orderTypes);

        $orders = $query
            ->orderBy('created_at', 'desc')
            ->paginate(100)
            ->withQueryString();

        $user = auth()->user();
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
            ],
            'serviceProviders' => $serviceProviders,
            'userCompanyRoles' => $userCompanyRoles,
        ]);
    }

    /**
     * Build the base query for orders index/export with search, status and order_type filters.
     *
     * @param  array<int, string>  $orderTypes  ['waste'], ['recycling'], or ['waste','recycling']/[] for all
     */
    private function ordersIndexQuery(?string $search, ?string $status, array $orderTypes): \Illuminate\Database\Eloquent\Builder
    {
        $query = Order::with(['site.branch.company', 'company', 'branch', 'creator', 'serviceProvider']);

        $user = auth()->user();
        if ($user && $user->company_id) {
            $query->where('company_id', $user->company_id);
        }

        $query->when($search, function ($q, $search) {
            $term = '%'.trim($search).'%';
            $q->where(function ($q) use ($term) {
                $q->where('orders.tracking_number', 'like', $term)
                    ->orWhere('orders.slip_number', 'like', $term)
                    ->orWhereHas('site', fn ($siteQ) => $siteQ->where('sites.name', 'like', $term))
                    ->orWhereHas('branch', fn ($branchQ) => $branchQ->where('branches.name', 'like', $term))
                    ->orWhereHas('company', fn ($companyQ) => $companyQ->where('companies.name', 'like', $term))
                    ->orWhereHas('serviceProvider', fn ($spQ) => $spQ->where('service_providers.name', 'like', $term));
            });
        });

        $query->when($status, fn ($q, $status) => $q->where('orders.status', $status));

        // Filter by order type only when exactly one type is selected (one checkbox = filter; both or none = all)
        if (count($orderTypes) === 1) {
            $query->where('orders.order_type', $orderTypes[0]);
        }

        return $query;
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
            ->where('is_active', true)
            ->where('order_type', 'waste')
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        $containerOptionsRecycling = ContainerOption::query()
            ->where('is_active', true)
            ->where('order_type', 'recycling')
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
                    $query->where('order_type', $orderType)->where('is_active', true);
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

    /**
     * Edit actual collection date on a finalized order (e.g. to fix dates set incorrectly).
     * Updating the date recalculates client monthly material summaries so they show in the correct month.
     */
    public function editCollectionDate(Order $order)
    {
        $this->ensureOrderInScope($order);

        if ($order->status !== 'finalized') {
            return redirect()->route('orders.show', $order)
                ->with('error', 'Only finalized orders can have their collection date corrected.');
        }

        $user = auth()->user();
        $companyId = $order->site?->branch?->company?->id ?? $order->company_id;
        if (! $user->canManageOrdersForCompany($companyId)) {
            abort(403, 'You do not have permission to edit this order.');
        }

        return Inertia::render('Orders/EditCollectionDate', [
            'order' => $order,
        ]);
    }

    public function updateCollectionDate(Request $request, Order $order)
    {
        $this->ensureOrderInScope($order);

        if ($order->status !== 'finalized') {
            return redirect()->route('orders.show', $order)
                ->with('error', 'Only finalized orders can have their collection date corrected.');
        }

        $user = auth()->user();
        $companyId = $order->site?->branch?->company?->id ?? $order->company_id;
        if (! $user->canManageOrdersForCompany($companyId)) {
            abort(403, 'You do not have permission to edit this order.');
        }

        $validated = $request->validate([
            'actual_collection_date' => 'required|date',
            ...$this->getEditReasonValidationRules(),
        ]);

        $oldActualCollectionDate = $order->actual_collection_date;

        $order->update([
            'actual_collection_date' => $validated['actual_collection_date'],
        ]);

        $order->refresh();
        app(ClientMonthlySummaryService::class)->moveOrderSummariesToActualCollectionDate($order, $oldActualCollectionDate);

        $oldDateFormatted = $oldActualCollectionDate ? Carbon::parse($oldActualCollectionDate)->format('Y-m-d') : null;
        $newDateFormatted = Carbon::parse($validated['actual_collection_date'])->format('Y-m-d');

        $reasonLabel = $this->getEditReasonLabel($validated['reason'], $validated['reason_details'] ?? '');

        ActivityLog::log('order_collection_date_updated', "Order {$order->tracking_number} collection date changed from {$oldDateFormatted} to {$newDateFormatted} ({$reasonLabel})", $order, [
            'tracking_number' => $order->tracking_number,
            'old_date' => $oldDateFormatted,
            'new_date' => $newDateFormatted,
            'reason' => $validated['reason'],
            'reason_details' => $validated['reason_details'] ?? null,
        ]);

        return redirect()->route('orders.show', $order)
            ->with('success', 'Collection date updated. Client monthly material summaries have been recalculated for the correct month.');
    }

    public function updateStatus(Request $request, Order $order)
    {
        $this->ensureOrderInScope($order);

        $validated = $request->validate([
            'status' => 'required|in:pending,scheduled,weight_required,documents_required,finalized',
            'notes' => 'nullable|string|max:1000',
        ]);

        $newStatus = $validated['status'];
        $permission = match ($newStatus) {
            'scheduled' => 'orders-schedule',
            'weight_required' => 'orders-status-weight-required',
            'documents_required' => 'orders-status-documents-required',
            'finalized' => 'orders-finalize',
            default => null,
        };
        if ($permission && ! auth()->user()->can($permission)) {
            abort(403, 'You do not have permission to change status to '.str_replace('_', ' ', $newStatus).'.');
        }

        if (! $order->canTransitionTo($newStatus)) {
            return back()->withErrors([
                'status' => 'Invalid status transition from '.$order->status.' to '.$newStatus,
            ]);
        }

        $oldStatus = $order->status;
        $order->update([
            'status' => $newStatus,
        ]);

        if (! empty($validated['notes'])) {
            $this->statusHistoryRepository->createForOrder(
                $order->id,
                $newStatus,
                auth()->id(),
                $validated['notes']
            );
        }

        ActivityLog::log('order_status_changed', "Order {$order->tracking_number} status changed from {$oldStatus} to {$newStatus}", $order, [
            'tracking_number' => $order->tracking_number,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ]);

        return redirect()->route('orders.show', $order)
            ->with('success', 'Order status updated successfully.');
    }

    public function finalizeForm(Order $order)
    {
        $this->ensureOrderInScope($order);

        if ($order->status === 'finalized') {
            return redirect()->route('orders.show', $order)
                ->with('error', 'Order is already finalized.');
        }

        if ($order->status !== 'documents_required') {
            return redirect()->route('orders.show', $order)
                ->with('error', 'Order must be in "Documents Required" status before finalization. Please complete the workflow: Schedule → Capture Weights → Upload Documents.');
        }

        $order->load([
            'site.branch.company',
            'company',
            'branch',
            'creator',
            'serviceProvider',
            'wasteStreams.material.wasteStream',
            'wasteStreams.material.grade',
            'supportingDocuments',
        ]);

        if (! $order->relationLoaded('site')) {
            $order->load('site.branch.company');
        }

        $order->load('wasteStreams');
        $order->append(['can_be_finalized', 'has_required_supporting_documents']);

        $materialsQuery = Material::active()
            ->with(['wasteStream:id,name', 'grade:id,name'])
            ->whereHas('wasteStream');

        if ($order->order_type === 'waste') {
            $materialsQuery->whereHas('wasteStream', function ($q) {
                $q->where('name', 'like', '%Waste%');
            });
        }

        $materials = $materialsQuery->get();

        $order->append(['can_be_finalized', 'has_required_supporting_documents']);

        $user = auth()->user();
        $companyId = $order->site?->branch?->company?->id ?? $order->company_id;
        $canManageOrder = $user->isAdmin() || $user->canManageOrdersForCompany($companyId);

        $containerOptionsWithWeight = ContainerOption::query()
            ->where('order_type', $order->order_type)
            ->whereNotNull('default_weight')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'default_weight']);

        return Inertia::render('Orders/Finalize', [
            'order' => $order,
            'materials' => $materials,
            'canManageOrder' => $canManageOrder,
            'containerOptionsWithWeight' => $containerOptionsWithWeight,
        ]);
    }

    /**
     * Check if a slip number is already used (for uniqueness validation on finalize).
     * If duplicate, logs the attempt to activity log and returns exists: true.
     */
    public function checkSlipNumber(Request $request)
    {
        $validated = $request->validate([
            'slip_number' => 'required|string|max:255',
            'exclude_order_id' => 'nullable|integer|exists:orders,id',
        ]);

        $query = Order::where('slip_number', $validated['slip_number']);
        if (! empty($validated['exclude_order_id'])) {
            $query->where('id', '!=', $validated['exclude_order_id']);
        }
        $exists = $query->exists();

        if ($exists) {
            $subjectOrder = ! empty($validated['exclude_order_id']) ? Order::find($validated['exclude_order_id']) : null;
            ActivityLog::log('duplicate_slip_number', 'Duplicate slip number attempted: '.$validated['slip_number'], $subjectOrder, [
                'slip_number' => $validated['slip_number'],
                'exclude_order_id' => $validated['exclude_order_id'] ?? null,
            ]);
        }

        return response()->json(['exists' => $exists]);
    }

    public function saveWeights(Request $request, Order $order)
    {
        $this->ensureOrderInScope($order);
        abort_unless(auth()->user()->can('orders-capture-weights'), 403, 'You do not have permission to capture weights.');

        if ($order->status === 'finalized') {
            return response()->json([
                'message' => 'Cannot modify weights for finalized orders.',
            ], 422);
        }

        $validated = $request->validate([
            'weight_lines' => 'required|array',
            'weight_lines.*.material_id' => 'required|exists:materials,id',
            'weight_lines.*.weight' => 'required|numeric|min:0',
            'weight_lines.*.id' => 'nullable|exists:order_waste_streams,id',
        ]);

        $existingIds = collect($validated['weight_lines'])
            ->pluck('id')
            ->filter()
            ->toArray();

        $order->wasteStreams()
            ->whereNotIn('id', $existingIds)
            ->delete();

        $materialIds = collect($validated['weight_lines'])->pluck('material_id')->unique()->filter()->all();
        $materials = Material::with(['grade', 'wasteStream'])->whereIn('id', $materialIds)->get()->keyBy('id');

        foreach ($validated['weight_lines'] as $line) {
            $material = $materials->get($line['material_id']);
            $rebateRate = $material && $material->rebate_offered && $material->rebate_rate !== null
                ? round((float) $material->rebate_rate, 2)
                : null;

            if (isset($line['id']) && $line['id']) {
                $wasteStream = \App\Models\OrderWasteStream::find($line['id']);
                if ($wasteStream) {
                    $wasteStream->update([
                        'material_id' => $line['material_id'],
                        'nett_weight' => $line['weight'],
                        'gross_weight' => $line['weight'],
                        'rebate_rate' => $rebateRate,
                    ]);
                }
            } else {
                \App\Models\OrderWasteStream::create([
                    'order_id' => $order->id,
                    'material_id' => $line['material_id'],
                    'nett_weight' => $line['weight'],
                    'gross_weight' => $line['weight'],
                    'rebate_rate' => $rebateRate,
                ]);
            }
        }

        if ($order->status !== 'finalized') {
            $order->update(['status' => 'documents_required']);
        }

        $weightLinesSnapshot = collect($validated['weight_lines'])->map(function ($line) use ($materials) {
            $material = $materials->get($line['material_id']);
            $materialLabel = $material && $material->grade ? $material->grade->name : ($material ? "Material #{$material->id}" : "Material #{$line['material_id']}");

            return [
                'material_id' => (int) $line['material_id'],
                'material_name' => $materialLabel,
                'weight' => (float) $line['weight'],
            ];
        })->values()->all();

        ActivityLog::log('order_weights_saved', "Weights captured for order {$order->tracking_number}", $order, [
            'tracking_number' => $order->tracking_number,
            'weight_lines' => $weightLinesSnapshot,
        ]);

        return response()->json([
            'message' => 'Weights saved successfully.',
            'order' => $order->fresh(['wasteStreams.material.wasteStream', 'wasteStreams.material.grade']),
        ]);
    }

    public function finalize(Request $request, Order $order)
    {
        $this->ensureOrderInScope($order);
        abort_unless(auth()->user()->can('orders-finalize'), 403, 'You do not have permission to finalize orders.');

        if ($order->status === 'finalized') {
            return back()->withErrors([
                'status' => 'Order is already finalized.',
            ]);
        }

        if ($order->status !== 'documents_required') {
            return back()->withErrors([
                'status' => 'Order must be in "Documents Required" status before finalization. Current status: '.$order->status,
            ]);
        }

        if ($order->wasteStreams()->count() === 0) {
            return back()->withErrors([
                'weights' => 'Weights must be captured before finalizing the order.',
            ]);
        }

        if (! $order->hasRequiredSupportingDocuments()) {
            return back()->withErrors([
                'documents' => 'At least one supporting document is required to finalize the order.',
            ]);
        }

        $validated = $request->validate([
            'actual_collection_date' => 'required|date',
            'actual_quantity' => 'nullable|integer|min:0',
            'slip_number' => 'required|string|max:255',
        ]);

        $prefix = $order->serviceProvider?->slip_number_prefix;
        $prefix = $prefix ? trim((string) $prefix) : '';
        $slipInput = trim($validated['slip_number']);
        $fullSlipNumber = ($prefix !== '' && ! str_starts_with($slipInput, $prefix.'-'))
            ? $prefix.'-'.$slipInput
            : $slipInput;

        $request->validate([
            'slip_number' => [
                'required',
                'string',
                'max:255',
            ],
        ]);

        \Validator::make(
            ['slip_number' => $fullSlipNumber],
            ['slip_number' => [Rule::unique('orders', 'slip_number')->ignore($order->id)]]
        )->validate();

        $oldActualCollectionDate = $order->actual_collection_date;

        $company = $order->site?->branch?->company;
        $companyRebatePercentage = $company && isset($company->rebate_percentage) && $company->rebate_percentage !== null && $company->rebate_percentage !== ''
            ? round((float) $company->rebate_percentage, 2)
            : null;

        $order->update([
            'status' => 'finalized',
            'actual_collection_date' => $validated['actual_collection_date'],
            'actual_quantity' => $validated['actual_quantity'] ?? $order->estimated_quantity,
            'slip_number' => $fullSlipNumber,
            'company_rebate_percentage' => $companyRebatePercentage,
        ]);

        $order->load('wasteStreams.material');
        foreach ($order->wasteStreams as $stream) {
            $rebateRate = $stream->material && $stream->material->rebate_offered && $stream->material->rebate_rate !== null
                ? round((float) $stream->material->rebate_rate, 2)
                : null;
            $stream->update(['rebate_rate' => $rebateRate]);
        }

        // Move monthly summary weights from requested (or previous actual) month to actual collection month
        // so Grade Summary by month uses actual collection date
        $order->refresh();
        app(\App\Services\ClientMonthlySummaryService::class)->moveOrderSummariesToActualCollectionDate($order, $oldActualCollectionDate);

        $actualQuantity = $validated['actual_quantity'] ?? $order->estimated_quantity;

        ActivityLog::log('order_finalized', "Order {$order->tracking_number} finalized (slip: {$fullSlipNumber})", $order, [
            'tracking_number' => $order->tracking_number,
            'slip_number' => $fullSlipNumber,
            'actual_collection_date' => $validated['actual_collection_date'],
            'actual_quantity' => $actualQuantity,
        ]);

        return redirect()->route('orders.show', $order)
            ->with('success', 'Order finalized successfully.');
    }

    public function downloadPDF(Order $order)
    {
        $this->ensureOrderInScope($order);

        try {
            ini_set('memory_limit', '256M');
            set_time_limit(30);

            $order->load([
                'site.branch.company',
                'company',
                'branch',
                'creator',
                'serviceProvider',
                'wasteStreams.material.grade',
                'wasteStreams.material.wasteStream',
            ]);

            $order->append('total_rebate');

            $options = new Options;
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', false);
            $options->set('defaultFont', 'DejaVu Sans');

            $dompdf = new Dompdf($options);
            $html = view('orders.pdf', ['order' => $order])->render();

            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $filename = 'Order_'.$order->tracking_number.'_'.now()->format('Y-m-d').'.pdf';
            $output = $dompdf->output();

            unset($dompdf, $html, $order);

            return response()->streamDownload(function () use ($output) {
                echo $output;
            }, $filename, [
                'Content-Type' => 'application/pdf',
            ]);
        } catch (\Exception $e) {
            \Log::error('PDF Generation Error: '.$e->getMessage());

            return back()->withErrors(['pdf' => 'Failed to generate PDF. Please try again.']);
        }
    }

    public function exportPdf(Request $request)
    {
        $status = $request->input('status');
        $orderTypes = $request->has('order_types') ? (array) $request->input('order_types') : [];
        $orderTypes = array_values(array_unique(array_filter(array_map('strtolower', $orderTypes), fn ($t) => in_array($t, ['waste', 'recycling'], true))));

        $query = $this->ordersIndexQuery($request->input('search'), $status, $orderTypes);
        $orders = $query->orderBy('created_at', 'desc')->get();

        $options = new Options;
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $html = view('orders.export-pdf', ['orders' => $orders])->render();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = 'orders_export_'.now()->format('Y-m-d_His').'.pdf';

        return response()->streamDownload(function () use ($dompdf) {
            echo $dompdf->output();
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function exportCsv(Request $request)
    {
        $status = $request->input('status');
        $orderTypes = $request->has('order_types') ? (array) $request->input('order_types') : [];
        $orderTypes = array_values(array_unique(array_filter(array_map('strtolower', $orderTypes), fn ($t) => in_array($t, ['waste', 'recycling'], true))));

        $query = $this->ordersIndexQuery($request->input('search'), $status, $orderTypes);
        $orders = $query->with(['site.branch.company', 'company', 'branch', 'serviceProvider'])
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'orders_export_'.now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($orders) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Tracking Number', 'Company', 'Branch', 'Site', 'Service Provider', 'Order Type', 'Status', 'Requested Date', 'Actual Date', 'Slip Number']);
            foreach ($orders as $order) {
                $site = $order->site;
                $branch = $site?->branch ?? $order->branch;
                $company = $branch?->company ?? $order->company;
                fputcsv($out, [
                    $order->tracking_number,
                    $company?->name ?? '',
                    $branch?->name ?? '',
                    $site?->name ?? '',
                    $order->serviceProvider?->name ?? (is_string($order->service_provider) ? $order->service_provider : ''),
                    $order->order_type === 'waste' ? 'Waste Order' : 'Recycling Order',
                    $order->status,
                    $order->requested_collection_date?->format('Y-m-d') ?? '',
                    $order->actual_collection_date?->format('Y-m-d') ?? '',
                    $order->slip_number ?? '',
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function getServiceProvidersByDate(Request $request)
    {
        $validated = $request->validate([
            'collection_date' => 'required|date',
        ]);

        $collectionDate = \Carbon\Carbon::parse($validated['collection_date']);

        $serviceProviderIds = Order::where('requested_collection_date', $collectionDate->format('Y-m-d'))
            ->whereNotNull('service_provider_id')
            ->distinct()
            ->pluck('service_provider_id');

        $serviceProviders = ServiceProvider::whereIn('id', $serviceProviderIds)
            ->active()
            ->orderBy('name')
            ->get();

        return response()->json($serviceProviders);
    }

    public function downloadConsolidatedPDF(Request $request)
    {
        abort_unless(auth()->user()->can('orders-generate-consolidated'), 403, 'You do not have permission to generate consolidated orders.');

        $validated = $request->validate([
            'collection_date' => 'required|date',
            'service_provider_id' => 'required|exists:service_providers,id',
        ]);

        $collectionDate = \Carbon\Carbon::parse($validated['collection_date']);
        $serviceProvider = ServiceProvider::findOrFail($validated['service_provider_id']);

        $orders = Order::with(['site.branch.company', 'company', 'branch'])
            ->where('requested_collection_date', $collectionDate->format('Y-m-d'))
            ->where('service_provider_id', $serviceProvider->id)
            ->orderBy('created_at')
            ->get();

        foreach ($orders as $order) {
            if ($order->status === 'pending') {
                $order->update(['status' => 'scheduled']);
                ActivityLog::log('order_consolidated_pdf_scheduled', "Order {$order->tracking_number} set to scheduled via consolidated PDF", $order, [
                    'tracking_number' => $order->tracking_number,
                    'collection_date' => $collectionDate->format('Y-m-d'),
                    'service_provider_id' => $serviceProvider->id,
                ]);
            }
        }

        $orders->fresh();
        $orders->load(['site.branch.company', 'company', 'branch']);

        $options = new Options;
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $html = view('orders.consolidated-pdf', [
            'orders' => $orders,
            'serviceProvider' => $serviceProvider,
            'collectionDate' => $collectionDate,
        ])->render();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'Consolidated_Order_'.preg_replace('/[^a-zA-Z0-9_-]/', '_', $serviceProvider->name).'_'.$collectionDate->format('Y-m-d').'.pdf';

        return response()->streamDownload(function () use ($dompdf) {
            echo $dompdf->output();
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function rebateTracker(Request $request)
    {
        $user = $request->user();
        $companyIds = $user->isAdmin() ? [] : $this->companyUserService->getCompanyIdsForUser($user);

        if (! $user->isAdmin() && empty($companyIds)) {
            abort(403, 'No company assigned. Please contact administrator.');
        }

        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $companyId = $request->input('company_id') ? (int) $request->input('company_id') : null;
        $branchId = $request->input('branch_id') ? (int) $request->input('branch_id') : null;
        $siteId = $request->input('site_id') ? (int) $request->input('site_id') : null;

        [$companyId, $branchId, $siteId] = $this->enforceCompanyScope($companyId, $branchId, $siteId);

        $rebateData = $this->getRebateTrackerData($startDate, $endDate, $companyId, $branchId, $siteId, $user, $companyIds);

        $companies = $this->scopeCompaniesForUser();

        return Inertia::render('Reports/RebateTracker', [
            'rebateData' => $rebateData,
            'companies' => $companies,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'site_id' => $siteId,
            ],
            'totalRebate' => $rebateData->sum('total'),
            'totalWeight' => $rebateData->sum('weight'),
        ]);
    }

    public function rebateTrackerPdf(Request $request)
    {
        $user = $request->user();
        $companyIds = $user->isAdmin() ? [] : $this->companyUserService->getCompanyIdsForUser($user);

        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $companyId = $request->input('company_id') ? (int) $request->input('company_id') : null;
        $branchId = $request->input('branch_id') ? (int) $request->input('branch_id') : null;
        $siteId = $request->input('site_id') ? (int) $request->input('site_id') : null;

        [$companyId, $branchId, $siteId] = $this->enforceCompanyScope($companyId, $branchId, $siteId);

        $rebateData = $this->getRebateTrackerData($startDate, $endDate, $companyId, $branchId, $siteId, $user, $companyIds);

        $options = new \Dompdf\Options;
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new \Dompdf\Dompdf($options);
        $html = view('reports.rebate-tracker-pdf', [
            'rebateData' => $rebateData,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'site_id' => $siteId,
            ],
            'totalRebate' => $rebateData->sum('total'),
            'totalWeight' => $rebateData->sum('weight'),
        ])->render();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = 'Rebate_Tracker_'.$startDate.'_to_'.$endDate.'.pdf';

        return response()->streamDownload(function () use ($dompdf) {
            echo $dompdf->output();
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    private function getRebateTrackerData(string $startDate, string $endDate, ?string $companyId, ?string $branchId, ?string $siteId, $user, array $companyIds)
    {
        $query = OrderWasteStream::with([
            'order.site.branch.company',
            'order.supportingDocuments',
            'material.grade',
            'material.wasteStream',
        ])
            ->whereHas('order', function ($q) use ($startDate, $endDate, $companyId, $branchId, $siteId, $user, $companyIds) {
                $q->where('status', 'finalized')
                    ->where(function ($query) use ($startDate, $endDate) {
                        $query->whereBetween('actual_collection_date', [$startDate, $endDate])
                            ->orWhere(function ($q) use ($startDate, $endDate) {
                                $q->whereNull('actual_collection_date')
                                    ->whereBetween('requested_collection_date', [$startDate, $endDate]);
                            });
                    });
                if ($companyId) {
                    $q->where('company_id', $companyId);
                }
                if ($branchId) {
                    $q->where('branch_id', $branchId);
                }
                if ($siteId) {
                    $q->where('site_id', $siteId);
                }
                if (! $user->isAdmin()) {
                    $q->whereHas('site.branch.company', function ($q) use ($companyIds) {
                        $q->whereIn('companies.id', $companyIds);
                    });
                }
            })
            ->where(function ($q) {
                $q->whereNotNull('rebate_rate')->where('rebate_rate', '>', 0)
                    ->orWhereHas('material', function ($mq) {
                        $mq->where('rebate_offered', true);
                    });
            });

        return $query->get()->map(function ($stream) {
            $collectionDate = $stream->order->actual_collection_date ?? $stream->order->requested_collection_date;
            $site = $stream->order->site;
            $branch = $site?->branch;
            $company = $branch?->company;

            return [
                'id' => $stream->id,
                'order_id' => $stream->order_id,
                'tracking_number' => $stream->order->tracking_number ?? '—',
                'date' => $collectionDate,
                'company_name' => $company?->name ?? '—',
                'branch_name' => $branch?->name ?? '—',
                'site_name' => $site?->name ?? '—',
                'grade' => $stream->material->grade->name ?? '—',
                'weight' => $stream->nett_weight,
                'rate' => $stream->rebate_rate ?? $stream->material?->rebate_rate ?? 0,
                'total' => $stream->client_rebate_amount,
                'material_id' => $stream->material_id,
                'supporting_documents' => $stream->order->supportingDocuments->map(function ($doc) {
                    return [
                        'id' => $doc->id,
                        'original_name' => $doc->original_name,
                        'file_name' => $doc->file_name,
                    ];
                })->values()->toArray(),
            ];
        })->groupBy(function ($item) {
            return Carbon::parse($item['date'])->format('Y-m-d').'|'.($item['company_name'] ?? '').'|'.($item['branch_name'] ?? '').'|'.($item['site_name'] ?? '').'|'.$item['grade'];
        })->map(function ($group) {
            return [
                'date' => $group->first()['date'],
                'company_name' => $group->first()['company_name'],
                'branch_name' => $group->first()['branch_name'],
                'site_name' => $group->first()['site_name'],
                'grade' => $group->first()['grade'],
                'weight' => $group->sum('weight'),
                'rate' => $group->first()['rate'],
                'total' => $group->sum('total'),
            ];
        })->values()->sortBy(['company_name', 'branch_name', 'site_name', 'date']);
    }

    public function getAverageWeightForWheelieBins(Request $request)
    {
        $validated = $request->validate([
            'month' => 'nullable|date_format:Y-m',
            'site_id' => 'nullable|exists:sites,id',
            'container_type' => 'nullable|in:rel_skip,wheelie_bins,skips_30m2',
        ]);

        if (empty($validated['month'])) {
            $validated['month'] = Carbon::now()->format('Y-m');
        }

        $containerType = $validated['container_type'] ?? 'wheelie_bins';

        $startDate = Carbon::parse($validated['month'])->startOfMonth();
        $endDate = Carbon::parse($validated['month'])->endOfMonth();

        $containerTypeLabels = [
            'rel_skip' => 'REL Skip',
            'wheelie_bins' => 'Wheelie Bins',
            'skips_30m2' => '30m² Skips',
        ];

        $user = $request->user();
        $companyIds = $user->isAdmin() ? [] : $this->companyUserService->getCompanyIdsForUser($user);

        if (! $user->isAdmin() && empty($companyIds)) {
            abort(403, 'No company assigned. Please contact administrator.');
        }

        $query = OrderWasteStream::with(['order.site', 'material'])
            ->whereHas('order', function ($q) use ($startDate, $endDate, $validated, $containerType, $companyIds, $user) {
                $q->where('status', 'finalized')
                    ->where(function ($query) use ($startDate, $endDate) {
                        $query->whereBetween('actual_collection_date', [$startDate, $endDate])
                            ->orWhere(function ($q) use ($startDate, $endDate) {
                                $q->whereNull('actual_collection_date')
                                    ->whereBetween('requested_collection_date', [$startDate, $endDate]);
                            });
                    })
                    ->where(function ($query) use ($containerType) {
                        $query->whereJsonContains('quantity_lines', [['quantity_type' => $containerType]])
                            ->orWhere('quantity_type', $containerType);
                    });
                if (isset($validated['site_id'])) {
                    $q->where('site_id', $validated['site_id']);
                }
                if (! $user->isAdmin()) {
                    $q->whereHas('site.branch.company', function ($q) use ($companyIds) {
                        $q->whereIn('companies.id', $companyIds);
                    });
                }
            });

        $streams = $query->get();

        $sites = Site::with(['branch.company'])
            ->where('is_active', true)
            ->when(! $user->isAdmin(), function ($query) use ($companyIds) {
                $query->whereHas('branch.company', function ($q) use ($companyIds) {
                    $q->whereIn('companies.id', $companyIds);
                });
            })
            ->orderBy('name')
            ->get();

        if ($streams->isEmpty()) {
            return Inertia::render('Reports/AverageWeight', [
                'averageWeightData' => null,
                'sites' => $sites,
                'containerTypes' => $containerTypeLabels,
                'filters' => [
                    'month' => $validated['month'],
                    'site_id' => $validated['site_id'] ?? null,
                    'container_type' => $containerType,
                ],
            ]);
        }

        $totalWeight = $streams->sum('nett_weight');

        $ordersWithContainers = $streams->pluck('order')->unique('id');
        $totalContainers = $ordersWithContainers->sum(function ($order) use ($containerType) {
            if ($order->quantity_type === $containerType) {
                return $order->quantity ?? 0;
            }
            $quantityLines = $order->quantity_lines ?? [];
            if (is_array($quantityLines)) {
                $containerLine = collect($quantityLines)->firstWhere('quantity_type', $containerType);

                return $containerLine['quantity'] ?? 0;
            }

            return 0;
        });

        $averageWeight = $totalContainers > 0 ? $totalWeight / $totalContainers : 0;

        return Inertia::render('Reports/AverageWeight', [
            'averageWeightData' => $totalContainers > 0 ? [
                'month' => $validated['month'],
                'container_type' => $containerType,
                'container_type_label' => $containerTypeLabels[$containerType] ?? $containerType,
                'total_weight' => round($totalWeight, 3),
                'total_containers' => $totalContainers,
                'average_weight_per_container' => round($averageWeight, 3),
            ] : null,
            'sites' => $sites,
            'containerTypes' => $containerTypeLabels,
            'filters' => [
                'month' => $validated['month'],
                'site_id' => $validated['site_id'] ?? null,
                'container_type' => $containerType,
            ],
        ]);
    }

    public function destroy(Order $order)
    {
        return redirect()->route('orders.index')
            ->with('error', 'Please use the delete button in the actions column and select a reason.');
    }

    public function deleteOrder(Request $request, Order $order)
    {
        $user = auth()->user();

        $order->load('site.branch.company');
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

    private function getDeletionReasonLabel(string $reason, string $details): string
    {
        $labels = [
            'incorrect_order' => 'Incorrect order',
            'duplicate' => 'Duplicate order',
            'wrong_date' => 'Wrong collection date',
            'wrong_site' => 'Wrong site / collection point',
            'cancelled_by_client' => 'Cancelled by client',
            'other' => 'Other'.($details ? ": {$details}" : ''),
        ];

        return $labels[$reason] ?? $reason;
    }

    /**
     * Validation rules for "reason for edit" (order update, weights, collection date).
     *
     * @return array<string, mixed>
     */
    private function mapQuantityLinesWithContainerNames(array $lines): array
    {
        $containerOptionIds = collect($lines)->pluck('container_option_id')->unique()->filter()->all();
        $containerOptions = ContainerOption::whereIn('id', $containerOptionIds)->get()->keyBy('id');

        return collect($lines)->map(function ($line) use ($containerOptions) {
            $option = $containerOptions->get((int) $line['container_option_id']);
            $row = [
                'container_option_id' => (int) $line['container_option_id'],
                'container_option_name' => $option ? $option->name : '',
                'quantity' => (int) $line['quantity'],
            ];
            if (! empty($line['description'])) {
                $row['description'] = $line['description'];
            }

            return $row;
        })->all();
    }

    private function getEditReasonValidationRules(): array
    {
        return [
            'reason' => 'required|string|in:client_request,wrong_quantity,wrong_container_type,date_correction,data_entry_error,other',
            'reason_details' => 'required_if:reason,other|nullable|string|max:1000',
        ];
    }

    private function getEditReasonLabel(string $reason, string $details): string
    {
        $labels = [
            'client_request' => 'Client request',
            'wrong_quantity' => 'Wrong quantity entered',
            'wrong_container_type' => 'Wrong container type',
            'date_correction' => 'Date correction',
            'data_entry_error' => 'Data entry error',
            'other' => 'Other'.($details ? ": {$details}" : ''),
        ];

        return $labels[$reason] ?? $reason;
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\Site;
use App\Models\Company;
use App\Models\Branch;
use App\Models\WasteType;
use App\Models\Material;
use App\Models\ContainerOption;
use App\Models\ServiceProvider;
use App\Models\OrderWasteStream;
use App\Repositories\OrderStatusHistoryRepository;
use App\Traits\ScopeByClientTrait;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Dompdf\Dompdf;
use Dompdf\Options;
use Carbon\Carbon;

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
                // If status is empty, clear the session filter
                session()->forget('orders_status_filter');
            }
        } else {
            // If no status in request, use session value if available
            $status = session('orders_status_filter', null);
        }

        $query = Order::with(['site.branch.company', 'company', 'branch', 'creator', 'serviceProvider']);

        // Client-scoped: only orders for their company
        $user = auth()->user();
        if ($user && $user->company_id) {
            $query->where('company_id', $user->company_id);
        }

        $orders = $query
            ->when($request->search, function ($q, $search) {
                $q->where('tracking_number', 'like', "%{$search}%")
                    ->orWhere('slip_number', 'like', "%{$search}%")
                    ->orWhereHas('site', function ($siteQ) use ($search) {
                        $siteQ->where('name', 'like', "%{$search}%");
                    });
            })
            ->when($status, function ($q, $status) {
                $q->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(100)
            ->withQueryString();

        $serviceProviders = ServiceProvider::active()->get();

        $userCompanyRoles = [];
        if (!$user->isAdmin()) {
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
        $containerOptions = ContainerOption::where('is_active', true)->orderBy('name')->get(['id', 'name', 'slug']);

        return Inertia::render('Orders/Create', [
            'companies' => $companies,
            'branches' => $branches,
            'sites' => $sites,
            'materials' => $materials,
            'serviceProviders' => $serviceProviders,
            'containerOptions' => $containerOptions,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('orders-create'), 403, 'You do not have permission to create orders.');

        $orderType = $request->input('order_type');

        $recyclingQuantityTypes = 'scrap_load,loose_bags,cage_8m3,cage_20m3,other';
        $rules = [
            'company_id' => 'required|exists:companies,id',
            'branch_id' => 'required|exists:branches,id',
            'site_id' => 'nullable|exists:sites,id',
            'service_provider_id' => 'required|exists:service_providers,id',
            'order_type' => 'required|in:waste,recycling',
            'quantity_lines' => 'required|array|min:1',
            'quantity_lines.*.quantity' => 'required|integer|min:1',
            'requested_collection_date' => 'required|date|after_or_equal:today',
            'notes' => 'nullable|string|max:1000',
        ];

        if ($orderType === 'waste') {
            $rules['quantity_lines.*.container_option_id'] = 'required|exists:container_options,id';
        } else {
            $rules['quantity_lines.*.quantity_type'] = "required|in:{$recyclingQuantityTypes}";
            $rules['quantity_lines.*.description'] = 'nullable|string|max:255';
        }

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

        if ($orderType === 'recycling') {
            foreach ($validated['quantity_lines'] as $index => $line) {
                if (($line['quantity_type'] ?? '') === 'other' && empty($line['description'] ?? '')) {
                    return back()->withErrors([
                        "quantity_lines.{$index}.description" => 'Description is required when selecting "Other" container type.'
                    ]);
                }
            }
        }

        $validated['created_by'] = auth()->id();
        $validated['status'] = 'pending';

        if ($orderType === 'waste') {
            $containerOptionIds = collect($validated['quantity_lines'])->pluck('container_option_id')->unique()->filter()->all();
            $containerOptions = ContainerOption::whereIn('id', $containerOptionIds)->get()->keyBy('id');
            $validated['quantity_lines'] = collect($validated['quantity_lines'])->map(function ($line) use ($containerOptions) {
                $option = $containerOptions->get($line['container_option_id']);
                return [
                    'container_option_id' => (int) $line['container_option_id'],
                    'container_option_name' => $option ? $option->name : '',
                    'quantity' => (int) $line['quantity'],
                ];
            })->all();
        }

        $totalQuantity = collect($validated['quantity_lines'])->sum('quantity');
        $validated['estimated_quantity'] = $totalQuantity;

        $validated['site_id'] = ! empty($validated['site_id']) ? $validated['site_id'] : null;

        $order = Order::create($validated);

        return redirect()->route('orders.show', $order)
            ->with('success', 'Order created successfully! Tracking number: ' . $order->tracking_number);
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
            'statusHistory.changedBy'
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

        $order->load(['site.branch.company', 'company', 'branch', 'wasteStreams.wasteType']);
        $companyId = $order->site?->branch?->company?->id ?? $order->company_id;

        if (!$user->canManageOrdersForCompany($companyId)) {
            abort(403, 'Only managers can edit orders for this company. Viewers can only view orders.');
        }
        $sites = Site::with(['branch.company'])->get();

        $sites->each(function ($site) {
            $site->company_name = $site->getCompanyNameAttribute();
        });

        $wasteTypes = WasteType::active()->get();

        return Inertia::render('Orders/Edit', [
            'order' => $order,
            'sites' => $sites,
            'wasteTypes' => $wasteTypes,
        ]);
    }

    public function update(Request $request, Order $order)
    {
        $this->ensureOrderInScope($order);

        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'order_type' => 'required|in:waste,recycling',
            'status' => 'required|in:pending,scheduled,weight_required,documents_required,finalized',
            'requested_collection_date' => 'required|date',
            'actual_collection_date' => 'nullable|date',
            'service_provider' => 'nullable|string|max:255',
            'slip_number' => 'nullable|string|max:255',
            'estimated_quantity' => 'nullable|integer|min:1',
            'actual_quantity' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($request->has('status') && $request->status !== $order->status) {
            $newStatus = $request->status;
            $permission = match ($newStatus) {
                'scheduled' => 'orders-schedule',
                'weight_required' => 'orders-status-weight-required',
                'documents_required' => 'orders-status-documents-required',
                'finalized' => 'orders-finalize',
                default => null,
            };
            if ($permission && ! auth()->user()->can($permission)) {
                return back()->withErrors([
                    'status' => 'You do not have permission to change status to ' . str_replace('_', ' ', $newStatus) . '.'
                ]);
            }
            if (!$order->canTransitionTo($newStatus)) {
                return back()->withErrors([
                    'status' => 'Invalid status transition from ' . $order->status . ' to ' . $newStatus
                ]);
            }
        }

        $order->update($validated);

        return redirect()->route('orders.show', $order)
            ->with('success', 'Order updated successfully.');
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
            abort(403, 'You do not have permission to change status to ' . str_replace('_', ' ', $newStatus) . '.');
        }

        if (!$order->canTransitionTo($newStatus)) {
            return back()->withErrors([
                'status' => 'Invalid status transition from ' . $order->status . ' to ' . $newStatus
            ]);
        }

        $order->update([
            'status' => $newStatus,
        ]);

        if (!empty($validated['notes'])) {
            $this->statusHistoryRepository->createForOrder(
                $order->id,
                $newStatus,
                auth()->id(),
                $validated['notes']
            );
        }

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
            'supportingDocuments'
        ]);

        if (!$order->relationLoaded('site')) {
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

        $containerOptionsWithWeight = $order->order_type === 'waste'
            ? ContainerOption::whereNotNull('default_weight')->where('is_active', true)->orderBy('name')->get(['id', 'name', 'default_weight'])
            : [];

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
        if (!empty($validated['exclude_order_id'])) {
            $query->where('id', '!=', $validated['exclude_order_id']);
        }
        $exists = $query->exists();

        if ($exists) {
            ActivityLog::create([
                'log_name' => 'duplicate_slip_number',
                'description' => 'Duplicate slip number attempted: ' . $validated['slip_number'],
                'subject_type' => Order::class,
                'subject_id' => $validated['exclude_order_id'] ?? null,
                'causer_id' => $request->user()?->id,
                'properties' => [
                    'slip_number' => $validated['slip_number'],
                    'exclude_order_id' => $validated['exclude_order_id'] ?? null,
                ],
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
                'message' => 'Cannot modify weights for finalized orders.'
            ], 422);
        }

        $validated = $request->validate([
            'weight_lines' => 'required|array|min:1',
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

        foreach ($validated['weight_lines'] as $line) {
            if (isset($line['id']) && $line['id']) {
                $wasteStream = \App\Models\OrderWasteStream::find($line['id']);
                if ($wasteStream) {
                    $wasteStream->update([
                        'material_id' => $line['material_id'],
                        'nett_weight' => $line['weight'],
                        'gross_weight' => $line['weight'],
                    ]);
                }
            } else {
                \App\Models\OrderWasteStream::create([
                    'order_id' => $order->id,
                    'material_id' => $line['material_id'],
                    'nett_weight' => $line['weight'],
                    'gross_weight' => $line['weight'],
                ]);
            }
        }

        if ($order->status !== 'finalized') {
        $order->update(['status' => 'documents_required']);
        }

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
                'status' => 'Order is already finalized.'
            ]);
        }

        if ($order->status !== 'documents_required') {
            return back()->withErrors([
                'status' => 'Order must be in "Documents Required" status before finalization. Current status: ' . $order->status
            ]);
        }

        if ($order->wasteStreams()->count() === 0) {
            return back()->withErrors([
                'weights' => 'Weights must be captured before finalizing the order.'
            ]);
        }

        if (!$order->hasRequiredSupportingDocuments()) {
            return back()->withErrors([
                'documents' => 'At least one supporting document is required to finalize the order.'
            ]);
        }

        $validated = $request->validate([
            'actual_collection_date' => 'nullable|date',
            'actual_quantity' => 'nullable|integer|min:0',
            'slip_number' => 'required|string|max:255',
        ]);

        $prefix = $order->serviceProvider?->slip_number_prefix;
        $prefix = $prefix ? trim((string) $prefix) : '';
        $slipInput = trim($validated['slip_number']);
        $fullSlipNumber = ($prefix !== '' && ! str_starts_with($slipInput, $prefix . '-'))
            ? $prefix . '-' . $slipInput
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

        $order->update([
            'status' => 'finalized',
            'actual_collection_date' => $validated['actual_collection_date'] ?? $order->requested_collection_date,
            'actual_quantity' => $validated['actual_quantity'] ?? $order->estimated_quantity,
            'slip_number' => $fullSlipNumber,
        ]);

        // Move monthly summary weights from requested (or previous actual) month to actual collection month
        // so Grade Summary by month uses actual collection date
        $order->refresh();
        app(\App\Services\ClientMonthlySummaryService::class)->moveOrderSummariesToActualCollectionDate($order, $oldActualCollectionDate);

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

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $html = view('orders.pdf', ['order' => $order])->render();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'Order_' . $order->tracking_number . '_' . now()->format('Y-m-d') . '.pdf';
            $output = $dompdf->output();

            unset($dompdf, $html, $order);

            return response()->streamDownload(function () use ($output) {
                echo $output;
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
        } catch (\Exception $e) {
            \Log::error('PDF Generation Error: ' . $e->getMessage());
            return back()->withErrors(['pdf' => 'Failed to generate PDF. Please try again.']);
        }
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
            }
        }

        $orders->fresh();
        $orders->load(['site.branch.company', 'company', 'branch']);

        $options = new Options();
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

        $filename = 'Consolidated_Order_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $serviceProvider->name) . '_' . $collectionDate->format('Y-m-d') . '.pdf';

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

        if (!$user->isAdmin() && empty($companyIds)) {
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

        $options = new \Dompdf\Options();
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

        $filename = 'Rebate_Tracker_' . $startDate . '_to_' . $endDate . '.pdf';

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
            if (!$user->isAdmin()) {
                $q->whereHas('site.branch.company', function ($q) use ($companyIds) {
                    $q->whereIn('companies.id', $companyIds);
                });
            }
        })
        ->whereHas('material', function ($q) {
            $q->where('rebate_offered', true);
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
                'rate' => $stream->material->rebate_rate ?? 0,
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
            return Carbon::parse($item['date'])->format('Y-m-d') . '|' . ($item['company_name'] ?? '') . '|' . ($item['branch_name'] ?? '') . '|' . ($item['site_name'] ?? '') . '|' . $item['grade'];
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

        if (!$user->isAdmin() && empty($companyIds)) {
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
                if (!$user->isAdmin()) {
                    $q->whereHas('site.branch.company', function ($q) use ($companyIds) {
                        $q->whereIn('companies.id', $companyIds);
                    });
                }
            });

        $streams = $query->get();

        $sites = Site::with(['branch.company'])
            ->where('is_active', true)
            ->when(!$user->isAdmin(), function ($query) use ($companyIds) {
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
            ->with('error', 'Please use the delete button and select a reason. Only pending or scheduled orders can be deleted.');
    }

    public function deleteOrder(Request $request, Order $order)
    {
        $user = auth()->user();

        $order->load('site.branch.company');
        $companyId = $order->site?->branch?->company?->id ?? $order->company_id;

        if (!$user->canManageOrdersForCompany($companyId)) {
            abort(403, 'Only managers can delete orders for this company. Viewers can only view orders.');
        }

        if (!in_array($order->status, ['pending', 'scheduled'], true)) {
            return redirect()->route('orders.index')
                ->with('error', 'Only pending or scheduled orders can be deleted.');
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

        ActivityLog::create([
            'log_name' => 'order_deleted',
            'description' => "Order {$order->tracking_number} deleted: {$reasonLabel}",
            'subject_type' => Order::class,
            'subject_id' => $order->id,
            'causer_id' => $user->id,
            'properties' => [
                'order_id' => $order->id,
                'tracking_number' => $order->tracking_number,
                'reason' => $validated['reason'],
                'reason_details' => $validated['reason_details'] ?? null,
            ],
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
            'other' => 'Other' . ($details ? ": {$details}" : ''),
        ];

        return $labels[$reason] ?? $reason;
    }
}

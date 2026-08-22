<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\ContainerOption;
use App\Models\Material;
use App\Models\Order;
use App\Models\OrderWasteStream;
use App\Models\ServiceProvider;
use App\Repositories\OrderStatusHistoryRepository;
use App\Services\ClientMonthlySummaryService;
use App\Support\DisplayDate;
use App\Traits\HandlesOrderEditReasonsTrait;
use App\Traits\ScopeByClientTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class OrderWorkflowController extends Controller
{
    use HandlesOrderEditReasonsTrait, ScopeByClientTrait;

    public function __construct(
        protected OrderStatusHistoryRepository $statusHistoryRepository,
        protected ClientMonthlySummaryService $clientMonthlySummaryService,
    ) {}

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

        DB::transaction(function () use ($order, $validated, $oldActualCollectionDate) {
            $order->update([
                'actual_collection_date' => $validated['actual_collection_date'],
            ]);

            $order->refresh();
            $this->clientMonthlySummaryService->moveOrderSummariesToActualCollectionDate($order, $oldActualCollectionDate);

            $oldDateFormatted = $oldActualCollectionDate ? Carbon::parse($oldActualCollectionDate)->format(DisplayDate::CALENDAR) : null;
            $newDateFormatted = Carbon::parse($validated['actual_collection_date'])->format(DisplayDate::CALENDAR);

            $reasonLabel = $this->getEditReasonLabel($validated['reason'], $validated['reason_details'] ?? '');

            ActivityLog::log('order_collection_date_updated', "Order {$order->tracking_number} collection date changed from {$oldDateFormatted} to {$newDateFormatted} ({$reasonLabel})", $order, [
                'tracking_number' => $order->tracking_number,
                'old_date' => $oldActualCollectionDate ? Carbon::parse($oldActualCollectionDate)->format('Y-m-d') : null,
                'new_date' => Carbon::parse($validated['actual_collection_date'])->format('Y-m-d'),
                'reason' => $validated['reason'],
                'reason_details' => $validated['reason_details'] ?? null,
            ]);
        });

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
            return redirect()->route('orders.show', $order)
                ->with('error', "Cannot transition order from {$order->status} to {$newStatus}.");
        }

        $oldStatus = $order->status;
        $order->status = $newStatus;
        if (! empty($validated['notes'])) {
            $order->notes = $validated['notes'];
        }
        $order->save();

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

        $serviceProviders = ServiceProvider::active()->get();

        return Inertia::render('Orders/Finalize', [
            'order' => $order,
            'materials' => $materials,
            'canManageOrder' => $canManageOrder,
            'containerOptionsWithWeight' => $containerOptionsWithWeight,
            'serviceProviders' => $serviceProviders,
        ]);
    }

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
            'weight_lines.*.service_provider_id' => 'nullable|exists:service_providers,id',
        ]);

        $existingIds = collect($validated['weight_lines'])
            ->pluck('id')
            ->filter()
            ->toArray();

        $materialIds = collect($validated['weight_lines'])->pluck('material_id')->unique()->filter()->all();
        $materials = Material::with(['grade', 'wasteStream'])->whereIn('id', $materialIds)->get()->keyBy('id');

        DB::transaction(function () use ($order, $validated, $existingIds, $materials) {
            $order->wasteStreams()
                ->whereNotIn('id', $existingIds)
                ->delete();

            foreach ($validated['weight_lines'] as $line) {
                $material = $materials->get($line['material_id']);
                $rebateRate = $material && $material->rebate_offered && $material->rebate_rate !== null
                    ? round((float) $material->rebate_rate, 2)
                    : null;
                $serviceProviderId = $line['service_provider_id'] ?? $order->service_provider_id;

                if (isset($line['id']) && $line['id']) {
                    $wasteStream = OrderWasteStream::find($line['id']);
                    if ($wasteStream) {
                        $wasteStream->update([
                            'material_id' => $line['material_id'],
                            'nett_weight' => $line['weight'],
                            'gross_weight' => $line['weight'],
                            'rebate_rate' => $rebateRate,
                            'service_provider_id' => $serviceProviderId,
                        ]);
                    }
                } else {
                    OrderWasteStream::create([
                        'order_id' => $order->id,
                        'material_id' => $line['material_id'],
                        'nett_weight' => $line['weight'],
                        'gross_weight' => $line['weight'],
                        'rebate_rate' => $rebateRate,
                        'service_provider_id' => $serviceProviderId,
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
        });

        return response()->json([
            'message' => 'Weights saved successfully.',
            'order' => $order->fresh(['wasteStreams.material.wasteStream', 'wasteStreams.material.grade', 'wasteStreams.serviceProvider']),
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

        $actualQuantity = $validated['actual_quantity'] ?? $order->estimated_quantity;

        DB::transaction(function () use ($order, $validated, $fullSlipNumber, $companyRebatePercentage, $oldActualCollectionDate, $actualQuantity) {
            $order->update([
                'status' => 'finalized',
                'actual_collection_date' => $validated['actual_collection_date'],
                'actual_quantity' => $actualQuantity,
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

            $order->refresh();
            $this->clientMonthlySummaryService->moveOrderSummariesToActualCollectionDate($order, $oldActualCollectionDate);

            ActivityLog::log('order_finalized', "Order {$order->tracking_number} finalized (slip: {$fullSlipNumber})", $order, [
                'tracking_number' => $order->tracking_number,
                'slip_number' => $fullSlipNumber,
                'actual_collection_date' => Carbon::parse($validated['actual_collection_date'])->format('Y-m-d'),
                'actual_quantity' => $actualQuantity,
            ]);
        });

        return redirect()->route('orders.show', $order)
            ->with('success', 'Order finalized successfully.');
    }
}

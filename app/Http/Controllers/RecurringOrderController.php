<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRecurringOrderRequest;
use App\Http\Requests\UpdateRecurringOrderRequest;
use App\Models\Branch;
use App\Models\Company;
use App\Models\ContainerOption;
use App\Models\RecurringOrder;
use App\Models\ServiceProvider;
use App\Models\Site;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RecurringOrderController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('manage-recurring-orders'), 403);

        $recurringOrders = RecurringOrder::with(['company', 'branch', 'site', 'serviceProvider'])
            ->withTrashed()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($r) => $this->formatForIndex($r));

        return Inertia::render('RecurringOrders/Index', [
            'recurringOrders' => $recurringOrders,
        ]);
    }

    public function create(Request $request)
    {
        abort_unless($request->user()->can('manage-recurring-orders'), 403);

        return Inertia::render('RecurringOrders/Form', $this->formProps());
    }

    public function store(StoreRecurringOrderRequest $request)
    {
        $validated = $request->validated();
        $validated['created_by'] = $request->user()->id;
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['quantity_lines'] = $this->mapQuantityLines($validated['quantity_lines']);

        RecurringOrder::create($validated);

        return redirect()->route('recurring-orders.index')
            ->with('success', 'Recurring order created successfully.');
    }

    public function edit(Request $request, RecurringOrder $recurringOrder)
    {
        abort_unless($request->user()->can('manage-recurring-orders'), 403);

        return Inertia::render('RecurringOrders/Form', array_merge(
            $this->formProps(),
            ['recurringOrder' => $recurringOrder->load(['company', 'branch', 'site', 'serviceProvider'])]
        ));
    }

    public function update(UpdateRecurringOrderRequest $request, RecurringOrder $recurringOrder)
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['quantity_lines'] = $this->mapQuantityLines($validated['quantity_lines']);

        $recurringOrder->update($validated);

        return redirect()->route('recurring-orders.index')
            ->with('success', 'Recurring order updated successfully.');
    }

    public function destroy(Request $request, RecurringOrder $recurringOrder)
    {
        abort_unless($request->user()->can('manage-recurring-orders'), 403);

        $recurringOrder->delete();

        return redirect()->route('recurring-orders.index')
            ->with('success', 'Recurring order deleted.');
    }

    public function restore(Request $request, int $id)
    {
        abort_unless($request->user()->can('manage-recurring-orders'), 403);

        $recurringOrder = RecurringOrder::withTrashed()->findOrFail($id);
        $recurringOrder->restore();

        return redirect()->route('recurring-orders.index')
            ->with('success', 'Recurring order restored.');
    }

    private function formProps(): array
    {
        return [
            'companies' => Company::where('is_active', true)->orderBy('name')->get(['id', 'name', 'default_waste_service_provider_id', 'default_recycling_service_provider_id']),
            'branches' => Branch::where('is_active', true)->orderBy('name')->get(['id', 'company_id', 'name']),
            'sites' => Site::where('is_active', true)->orderBy('name')->get(['id', 'branch_id', 'name']),
            'serviceProviders' => ServiceProvider::active()->get(['id', 'name', 'types']),
            'containerOptionsWaste' => ContainerOption::where('order_type', 'waste')->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'containerOptionsRecycling' => ContainerOption::where('order_type', 'recycling')->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'days' => RecurringOrder::DAYS,
        ];
    }

    private function formatForIndex(RecurringOrder $r): array
    {
        return [
            'id' => $r->id,
            'order_type' => $r->order_type,
            'company' => $r->company?->name,
            'branch' => $r->branch?->name,
            'site' => $r->site?->name,
            'service_provider' => $r->serviceProvider?->name,
            'days_of_week' => $r->days_of_week,
            'days_label' => $r->daysLabel(),
            'quantity_lines' => $r->quantity_lines,
            'notes' => $r->notes,
            'is_active' => $r->is_active,
            'deleted_at' => $r->deleted_at?->toDateTimeString(),
        ];
    }

    private function mapQuantityLines(array $lines): array
    {
        $ids = collect($lines)->pluck('container_option_id')->unique()->filter()->all();
        $options = ContainerOption::whereIn('id', $ids)->get()->keyBy('id');

        return collect($lines)->map(function ($line) use ($options) {
            $option = $options->get((int) $line['container_option_id']);
            $row = [
                'container_option_id' => (int) $line['container_option_id'],
                'container_option_name' => $option?->name ?? '',
                'quantity' => (int) $line['quantity'],
            ];
            if (! empty($line['description'])) {
                $row['description'] = $line['description'];
            }

            return $row;
        })->all();
    }
}

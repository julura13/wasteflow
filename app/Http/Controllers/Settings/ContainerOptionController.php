<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ContainerOption;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ContainerOptionController extends Controller
{
    public function index(Request $request): Response
    {
        $containerOptions = ContainerOption::query()
            ->orderBy('order_type')
            ->orderBy('name')
            ->when($request->search, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Settings/ContainerOptions/Index', [
            'containerOptions' => $containerOptions,
            'filters' => $request->only('search'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'order_type' => 'required|string|in:waste,recycling',
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('container_options', 'name')->where(fn ($query) => $query->where('order_type', $request->input('order_type'))),
            ],
            'is_active' => 'sometimes|boolean',
            'default_weight' => 'nullable|numeric|min:0',
        ]);

        $data['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;
        $data['default_weight'] = $request->filled('default_weight') ? (float) $request->input('default_weight') : null;

        $containerOption = ContainerOption::create($data);
        ActivityLog::log('container_option_created', "Container option {$containerOption->name} created", $containerOption, [
            'name' => $containerOption->name,
            'order_type' => $containerOption->order_type,
        ]);

        return back()->with('success', 'Container option created successfully.');
    }

    public function update(Request $request, ContainerOption $containerOption): RedirectResponse
    {
        $data = $request->validate([
            'order_type' => 'required|string|in:waste,recycling',
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('container_options', 'name')
                    ->where(fn ($query) => $query->where('order_type', $request->input('order_type')))
                    ->ignore($containerOption->id),
            ],
            'is_active' => 'sometimes|boolean',
            'default_weight' => 'nullable|numeric|min:0',
        ]);

        $data['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;
        $data['default_weight'] = $request->filled('default_weight') ? (float) $request->input('default_weight') : null;

        $containerOption->update($data);
        ActivityLog::log('container_option_updated', "Container option {$containerOption->name} updated", $containerOption, [
            'name' => $containerOption->name,
            'order_type' => $containerOption->order_type,
        ]);

        return back()->with('success', 'Container option updated successfully.');
    }

    public function destroy(ContainerOption $containerOption): RedirectResponse
    {
        ActivityLog::log('container_option_deleted', "Container option {$containerOption->name} deleted", $containerOption, ['name' => $containerOption->name]);
        $containerOption->delete();

        return back()->with('success', 'Container option deleted successfully.');
    }
}

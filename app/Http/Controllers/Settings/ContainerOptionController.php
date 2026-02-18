<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\ContainerOption;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContainerOptionController extends Controller
{
    public function index(Request $request): Response
    {
        $containerOptions = ContainerOption::query()
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
            'name' => 'required|string|max:255|unique:container_options,name',
            'is_active' => 'sometimes|boolean',
            'default_weight' => 'nullable|numeric|min:0',
        ]);

        $data['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;
        $data['default_weight'] = $request->filled('default_weight') ? (float) $request->input('default_weight') : null;

        ContainerOption::create($data);

        return back()->with('success', 'Container option created successfully.');
    }

    public function update(Request $request, ContainerOption $containerOption): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:container_options,name,' . $containerOption->id,
            'is_active' => 'sometimes|boolean',
            'default_weight' => 'nullable|numeric|min:0',
        ]);

        $data['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;
        $data['default_weight'] = $request->filled('default_weight') ? (float) $request->input('default_weight') : null;

        $containerOption->update($data);

        return back()->with('success', 'Container option updated successfully.');
    }

    public function destroy(ContainerOption $containerOption): RedirectResponse
    {
        $containerOption->delete();

        return back()->with('success', 'Container option deleted successfully.');
    }
}

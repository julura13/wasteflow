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
            'description' => 'nullable|string|max:1000',
            'is_active' => 'sometimes|boolean',
        ]);

        $data['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;

        ContainerOption::create($data);

        return back()->with('success', 'Container option created successfully.');
    }

    public function update(Request $request, ContainerOption $containerOption): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:container_options,name,' . $containerOption->id,
            'description' => 'nullable|string|max:1000',
            'is_active' => 'sometimes|boolean',
        ]);

        $data['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;

        $containerOption->update($data);

        return back()->with('success', 'Container option updated successfully.');
    }

    public function destroy(ContainerOption $containerOption): RedirectResponse
    {
        $containerOption->delete();

        return back()->with('success', 'Container option deleted successfully.');
    }
}

<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Classification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClassificationController extends Controller
{
    public function index(Request $request): Response
    {
        $classifications = Classification::query()
            ->orderBy('name')
            ->when($request->search, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Settings/Classifications/Index', [
            'classifications' => $classifications,
            'filters' => $request->only('search'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:classifications,name',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'sometimes|boolean',
        ]);

        $data['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;

        Classification::create($data);

        return back()->with('success', 'Classification created successfully.');
    }

    public function update(Request $request, Classification $classification): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:classifications,name,' . $classification->id,
            'description' => 'nullable|string|max:1000',
            'is_active' => 'sometimes|boolean',
        ]);

        $data['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;

        $classification->update($data);

        return back()->with('success', 'Classification updated successfully.');
    }

    public function destroy(Classification $classification): RedirectResponse
    {
        $classification->delete();

        return back()->with('success', 'Classification deleted successfully.');
    }
}

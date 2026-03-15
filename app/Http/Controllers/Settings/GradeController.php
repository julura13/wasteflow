<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Grade;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GradeController extends Controller
{
    public function index(Request $request): Response
    {
        $grades = Grade::query()
            ->orderBy('name')
            ->when($request->search, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Settings/Grades/Index', [
            'grades' => $grades,
            'filters' => $request->only('search'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:grades,name',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'sometimes|boolean',
        ]);

        $data['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;

        $grade = Grade::create($data);
        ActivityLog::log('grade_created', "Grade {$grade->name} created", $grade, ['name' => $grade->name]);

        return back()->with('success', 'Grade created successfully.');
    }

    public function update(Request $request, Grade $grade): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:grades,name,'.$grade->id,
            'description' => 'nullable|string|max:1000',
            'is_active' => 'sometimes|boolean',
        ]);

        $data['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;

        $grade->update($data);
        ActivityLog::log('grade_updated', "Grade {$grade->name} updated", $grade, ['name' => $grade->name]);

        return back()->with('success', 'Grade updated successfully.');
    }

    public function destroy(Grade $grade): RedirectResponse
    {
        ActivityLog::log('grade_deleted', "Grade {$grade->name} deleted", $grade, ['name' => $grade->name]);
        $grade->delete();

        return back()->with('success', 'Grade deleted successfully.');
    }
}

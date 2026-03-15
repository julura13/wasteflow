<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\WasteStream;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WasteStreamController extends Controller
{
    public function index(Request $request): Response
    {
        $wasteStreams = WasteStream::query()
            ->orderBy('name')
            ->when($request->search, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Settings/WasteStreams/Index', [
            'wasteStreams' => $wasteStreams,
            'filters' => $request->only('search'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:waste_streams,name',
            'description' => 'nullable|string|max:1000',
            'is_default' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
        ]);

        $data['is_default'] = $request->boolean('is_default');
        $data['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;

        $wasteStream = WasteStream::create($data);
        ActivityLog::log('waste_stream_created', "Waste stream {$wasteStream->name} created", $wasteStream, ['name' => $wasteStream->name]);

        return back()->with('success', 'Waste stream created successfully.');
    }

    public function update(Request $request, WasteStream $wasteStream): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:waste_streams,name,'.$wasteStream->id,
            'description' => 'nullable|string|max:1000',
            'is_default' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
        ]);

        $data['is_default'] = $request->boolean('is_default');
        $data['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;

        $wasteStream->update($data);
        ActivityLog::log('waste_stream_updated', "Waste stream {$wasteStream->name} updated", $wasteStream, ['name' => $wasteStream->name]);

        return back()->with('success', 'Waste stream updated successfully.');
    }

    public function destroy(WasteStream $wasteStream): RedirectResponse
    {
        ActivityLog::log('waste_stream_deleted', "Waste stream {$wasteStream->name} deleted", $wasteStream, ['name' => $wasteStream->name]);
        $wasteStream->delete();

        return back()->with('success', 'Waste stream deleted successfully.');
    }
}

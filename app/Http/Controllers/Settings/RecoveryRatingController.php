<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\RecoveryRatingTier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class RecoveryRatingController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Settings/RecoveryRating/Index', [
            'tiers' => RecoveryRatingTier::query()->orderByDesc('sort_order')->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tiers' => 'required|array',
            'tiers.*.id' => 'required|integer|exists:recovery_rating_tiers,id',
            'tiers.*.min_percentage' => 'required|numeric|min:0|max:100',
        ]);

        $tiers = RecoveryRatingTier::query()->orderByDesc('sort_order')->get();
        $submitted = collect($data['tiers'])->keyBy('id');

        $ordered = $tiers->map(fn (RecoveryRatingTier $tier) => (float) $submitted[$tier->id]['min_percentage']);

        Validator::make(['thresholds' => $ordered->values()->all()], [], [])
            ->after(function ($validator) use ($ordered) {
                if ($ordered->values()->all() !== $ordered->sortDesc()->values()->all()) {
                    $validator->errors()->add('tiers', 'Each tier\'s minimum must be lower than the tier above it.');
                }
            })
            ->validate();

        foreach ($tiers as $tier) {
            $tier->update(['min_percentage' => $submitted[$tier->id]['min_percentage']]);
        }

        ActivityLog::log('recovery_rating_tiers_updated', 'Resource Recovery Rating thresholds updated');

        return back()->with('success', 'Recovery rating thresholds updated successfully.');
    }
}

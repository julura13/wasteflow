<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\RecoveryRatingTier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $tierCount = RecoveryRatingTier::query()->count();

        $data = $request->validate([
            'tiers' => "required|array|size:{$tierCount}",
            'tiers.*.id' => 'required|integer|distinct|exists:recovery_rating_tiers,id',
            'tiers.*.min_percentage' => 'required|numeric|min:0|max:100',
        ]);

        // 'tiers.*.id' distinct + exists + the array's fixed size together guarantee the
        // submitted ids are exactly the full set of existing tiers - no id can be missing
        // or repeated, so every lookup below is guaranteed to hit.
        $tiers = RecoveryRatingTier::query()->orderByDesc('sort_order')->get();
        $submitted = collect($data['tiers'])->keyBy('id');

        $ordered = $tiers->map(fn (RecoveryRatingTier $tier) => (float) $submitted[$tier->id]['min_percentage']);

        Validator::make(['thresholds' => $ordered->values()->all()], [], [])
            ->after(function ($validator) use ($ordered) {
                $values = $ordered->values();
                for ($i = 1; $i < $values->count(); $i++) {
                    if ($values[$i] >= $values[$i - 1]) {
                        $validator->errors()->add('tiers', 'Each tier\'s minimum must be lower than the tier above it.');
                        break;
                    }
                }
            })
            ->validate();

        DB::transaction(function () use ($tiers, $submitted) {
            foreach ($tiers as $tier) {
                $tier->update(['min_percentage' => $submitted[$tier->id]['min_percentage']]);
            }
        });

        ActivityLog::log('recovery_rating_tiers_updated', 'Resource Recovery Rating thresholds updated');

        return back()->with('success', 'Recovery rating thresholds updated successfully.');
    }
}

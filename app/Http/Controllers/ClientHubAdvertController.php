<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientHubAdvertRequest;
use App\Http\Requests\UpdateClientHubAdvertRequest;
use App\Models\ActivityLog;
use App\Models\ClientHubAdvert;
use App\Models\ClientHubAdvertView;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Client Hub adverts (WCP-39): admin-uploaded PNG/JPG/PDF announcements shown to client-role
 * users as a popup on login. Each user's relationship to an advert has two independent flags -
 * dismissed_at (popup closed, stops it auto-popping again) and read_at (advert actually opened,
 * clears the notification badge) - tracked in ClientHubAdvertView.
 */
class ClientHubAdvertController extends Controller
{
    /**
     * Admin management list of all adverts.
     */
    public function index(): Response
    {
        $adverts = ClientHubAdvert::query()
            ->with('uploadedBy:id,name')
            ->latest()
            ->get()
            ->map(fn (ClientHubAdvert $advert) => [
                'id' => $advert->id,
                'title' => $advert->title,
                'details' => $advert->details,
                'contact_email' => $advert->contact_email,
                'original_name' => $advert->original_name,
                'mime_type' => $advert->mime_type,
                'human_readable_size' => $advert->human_readable_size,
                'is_active' => $advert->is_active,
                'view_url' => route('client-hub.view', $advert->id),
                'uploaded_by' => $advert->uploadedBy?->name,
                'created_at' => $advert->created_at->format('Y-m-d H:i'),
            ]);

        return Inertia::render('Settings/ClientHub/Index', [
            'adverts' => $adverts,
        ]);
    }

    public function store(StoreClientHubAdvertRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        /** @var UploadedFile $file */
        $file = $request->file('file');
        $disk = config('filesystems.default');
        $fileName = Str::uuid().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs('client-hub', $fileName, $disk);

        $advert = ClientHubAdvert::create([
            'title' => $validated['title'],
            'details' => $validated['details'] ?? null,
            'contact_email' => $validated['contact_email'] ?? 'crm@wasteflow.example.com',
            'file_name' => $fileName,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'disk' => $disk,
            'path' => $path,
            'file_size' => $file->getSize(),
            'is_active' => $request->boolean('is_active', true),
            'uploaded_by' => $request->user()->id,
        ]);

        ActivityLog::log('client_hub_advert_uploaded', "Client Hub advert \"{$advert->title}\" uploaded", $advert, [
            'advert_id' => $advert->id,
            'title' => $advert->title,
        ]);

        return back()->with('success', 'Advert uploaded successfully.');
    }

    public function update(UpdateClientHubAdvertRequest $request, ClientHubAdvert $clientHubAdvert): RedirectResponse
    {
        $validated = $request->validated();

        $clientHubAdvert->title = $validated['title'];
        $clientHubAdvert->details = $validated['details'] ?? null;
        $clientHubAdvert->contact_email = $validated['contact_email'] ?? 'crm@wasteflow.example.com';
        $clientHubAdvert->is_active = $request->boolean('is_active', $clientHubAdvert->is_active);

        if ($request->hasFile('file')) {
            $oldDisk = Storage::disk($clientHubAdvert->disk);
            if ($oldDisk->exists($clientHubAdvert->path)) {
                $oldDisk->delete($clientHubAdvert->path);
            }

            /** @var UploadedFile $file */
            $file = $request->file('file');
            $disk = config('filesystems.default');
            $fileName = Str::uuid().'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs('client-hub', $fileName, $disk);

            $clientHubAdvert->file_name = $fileName;
            $clientHubAdvert->original_name = $file->getClientOriginalName();
            $clientHubAdvert->mime_type = $file->getMimeType();
            $clientHubAdvert->disk = $disk;
            $clientHubAdvert->path = $path;
            $clientHubAdvert->file_size = $file->getSize();
        }

        $clientHubAdvert->save();

        ActivityLog::log('client_hub_advert_updated', "Client Hub advert \"{$clientHubAdvert->title}\" updated", $clientHubAdvert, [
            'advert_id' => $clientHubAdvert->id,
            'title' => $clientHubAdvert->title,
        ]);

        return back()->with('success', 'Advert updated successfully.');
    }

    public function destroy(ClientHubAdvert $clientHubAdvert): RedirectResponse
    {
        $title = $clientHubAdvert->title;

        ActivityLog::log('client_hub_advert_deleted', "Client Hub advert \"{$title}\" deleted", $clientHubAdvert, [
            'advert_id' => $clientHubAdvert->id,
            'title' => $title,
        ]);

        $clientHubAdvert->delete();

        return back()->with('success', 'Advert deleted successfully.');
    }

    /**
     * Serve the advert's image/PDF inline (popup, bell dialog, and admin preview all use this).
     */
    public function view(ClientHubAdvert $clientHubAdvert)
    {
        $disk = Storage::disk($clientHubAdvert->disk);

        if (! $disk->exists($clientHubAdvert->path)) {
            abort(404, 'File not found.');
        }

        return $disk->response($clientHubAdvert->path, $clientHubAdvert->original_name, [
            'Content-Type' => $clientHubAdvert->mime_type,
        ]);
    }

    /**
     * The client closed the popup without opening it from the bell. Stops it auto-popping again,
     * but deliberately leaves read_at untouched so the notification badge still shows unread.
     *
     * Uses an atomic upsert rather than updateOrCreate: two concurrent requests for the same
     * user/advert (e.g. a double-click, or the same advert dismissed from two tabs) would
     * otherwise both pass updateOrCreate's "no matching row" check and race to insert, tripping
     * the unique(client_hub_advert_id, user_id) constraint on the second insert.
     */
    public function dismiss(Request $request, ClientHubAdvert $clientHubAdvert): RedirectResponse
    {
        $now = now();

        ClientHubAdvertView::query()->upsert(
            [[
                'client_hub_advert_id' => $clientHubAdvert->id,
                'user_id' => $request->user()->id,
                'dismissed_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]],
            ['client_hub_advert_id', 'user_id'],
            ['dismissed_at', 'updated_at']
        );

        return back();
    }

    /**
     * The client actually opened the advert (from the bell). Marks both flags: read implies
     * dismissed, so the popup doesn't also reappear after they've already viewed it this way.
     * See dismiss() for why this is an atomic upsert rather than updateOrCreate.
     */
    public function read(Request $request, ClientHubAdvert $clientHubAdvert): RedirectResponse
    {
        $now = now();

        ClientHubAdvertView::query()->upsert(
            [[
                'client_hub_advert_id' => $clientHubAdvert->id,
                'user_id' => $request->user()->id,
                'dismissed_at' => $now,
                'read_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]],
            ['client_hub_advert_id', 'user_id'],
            ['dismissed_at', 'read_at', 'updated_at']
        );

        return back();
    }

    /**
     * "Mark all as read" from the notification bell, for a client user. Marks every currently
     * unread active advert as both dismissed and read in one atomic upsert batch.
     */
    public function readAll(Request $request): RedirectResponse
    {
        $user = $request->user();
        $now = now();

        $advertIds = ClientHubAdvert::query()
            ->active()
            ->whereDoesntHave('views', fn ($q) => $q->where('user_id', $user->id)->whereNotNull('read_at'))
            ->pluck('id');

        if ($advertIds->isEmpty()) {
            return back();
        }

        ClientHubAdvertView::query()->upsert(
            $advertIds->map(fn (int $advertId) => [
                'client_hub_advert_id' => $advertId,
                'user_id' => $user->id,
                'dismissed_at' => $now,
                'read_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all(),
            ['client_hub_advert_id', 'user_id'],
            ['dismissed_at', 'read_at', 'updated_at']
        );

        return back();
    }
}

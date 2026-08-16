<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSheqComplianceDocumentRequest;
use App\Http\Requests\UpdateSheqComplianceDocumentRequest;
use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\Media;
use App\Models\User;
use App\Services\CompanyUserService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * SHEQ Compliance documents (Safety, Health, Environment & Quality / "HSE File").
 * Same shape and access rules as DocumentController, but standalone (not attached to
 * any Order/etc.) media rows filtered by collection = self::COLLECTION.
 *
 * Visibility: a document with no companies attached (via the media_company pivot) is
 * visible to every client, matching the original behaviour. Attaching companies restricts
 * it to client-role users belonging to one of those companies. Internal staff (anyone
 * without the client/company_user role) always see everything, regardless of restriction.
 */
class SheqComplianceController extends Controller
{
    private const COLLECTION = 'sheq_compliance';

    public function __construct(private readonly CompanyUserService $companyUserService) {}

    /**
     * Display a listing of SHEQ compliance documents, marking any unseen ones as seen.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $canManage = $user->can('manage-documents');

        $this->visibleTo(Media::query(), $user)
            ->unseenByUser($user->id)
            ->get()
            ->each(fn (Media $media) => $media->markSeenBy($user));

        $documents = $this->visibleTo(Media::query(), $user)
            ->with(['uploadedBy:id,name', 'companies:id,name'])
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->paginate(15)
            ->through(fn (Media $media) => [
                'id' => $media->id,
                'title' => $media->title,
                'description' => $media->description,
                'original_name' => $media->original_name,
                'human_readable_size' => $media->human_readable_size,
                'uploaded_by' => $media->uploadedBy?->name,
                'created_at' => $media->created_at->format('Y-m-d H:i'),
                'company_ids' => $canManage ? $media->companies->pluck('id') : null,
                'company_names' => $canManage ? $media->companies->pluck('name') : null,
            ]);

        return Inertia::render('SheqCompliance/Index', [
            'documents' => $documents,
            'companies' => $canManage ? Company::query()->orderBy('name')->get(['id', 'name']) : [],
        ]);
    }

    /**
     * Scope a Media query to only the SHEQ compliance documents the given user may see.
     */
    private function visibleTo(Builder $query, User $user): Builder
    {
        $query = $query->where('collection', self::COLLECTION)->whereNull('mediable_type');

        return $this->companyUserService->scopeVisibleToUser($query, $user);
    }

    /**
     * Store a newly uploaded SHEQ compliance document.
     */
    public function store(StoreSheqComplianceDocumentRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        /** @var UploadedFile $file */
        $file = $request->file('file');
        $disk = config('filesystems.default');
        $fileName = Str::uuid().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs('sheq-compliance', $fileName, $disk);

        // Locking the max() read inside the same transaction as the insert prevents two
        // concurrent uploads from both computing the same "next" sort_order - the second
        // transaction's locked read blocks until the first commits its new row.
        $document = DB::transaction(function () use ($validated, $file, $fileName, $disk, $path, $request) {
            $nextSortOrder = (int) (Media::where('collection', self::COLLECTION)
                ->whereNull('mediable_type')
                ->lockForUpdate()
                ->max('sort_order') ?? 0) + 1;

            return Media::create([
                'collection' => self::COLLECTION,
                'sort_order' => $nextSortOrder,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'original_name' => $file->getClientOriginalName(),
                'file_name' => $fileName,
                'mime_type' => $file->getMimeType(),
                'disk' => $disk,
                'path' => $path,
                'file_size' => $file->getSize(),
                'uploaded_by' => $request->user()->id,
            ]);
        });

        $document->companies()->sync($validated['company_ids'] ?? []);

        ActivityLog::log('sheq_compliance_document_uploaded', "SHEQ Compliance document \"{$document->title}\" uploaded", $document, [
            'media_id' => $document->id,
            'title' => $document->title,
        ]);

        return back()->with('success', 'Document uploaded successfully.');
    }

    /**
     * Update an existing SHEQ compliance document's details, optionally replacing the file.
     */
    public function update(UpdateSheqComplianceDocumentRequest $request, Media $sheqCompliance): RedirectResponse
    {
        abort_unless($sheqCompliance->collection === self::COLLECTION && $sheqCompliance->mediable_type === null, 404);

        $validated = $request->validated();

        $sheqCompliance->title = $validated['title'];
        $sheqCompliance->description = $validated['description'] ?? null;

        if ($request->hasFile('file')) {
            $oldDisk = Storage::disk($sheqCompliance->disk);
            if ($oldDisk->exists($sheqCompliance->path)) {
                $oldDisk->delete($sheqCompliance->path);
            }

            /** @var UploadedFile $file */
            $file = $request->file('file');
            $disk = config('filesystems.default');
            $fileName = Str::uuid().'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs('sheq-compliance', $fileName, $disk);

            $sheqCompliance->original_name = $file->getClientOriginalName();
            $sheqCompliance->file_name = $fileName;
            $sheqCompliance->mime_type = $file->getMimeType();
            $sheqCompliance->disk = $disk;
            $sheqCompliance->path = $path;
            $sheqCompliance->file_size = $file->getSize();
        }

        $sheqCompliance->save();

        $sheqCompliance->companies()->sync($validated['company_ids'] ?? []);

        ActivityLog::log('sheq_compliance_document_updated', "SHEQ Compliance document \"{$sheqCompliance->title}\" updated", $sheqCompliance, [
            'media_id' => $sheqCompliance->id,
            'title' => $sheqCompliance->title,
        ]);

        return back()->with('success', 'Document updated successfully.');
    }

    /**
     * Delete a SHEQ compliance document.
     */
    public function destroy(Media $sheqCompliance): RedirectResponse
    {
        abort_unless($sheqCompliance->collection === self::COLLECTION && $sheqCompliance->mediable_type === null, 404);

        $title = $sheqCompliance->title;

        ActivityLog::log('sheq_compliance_document_deleted', "SHEQ Compliance document \"{$title}\" deleted", $sheqCompliance, [
            'media_id' => $sheqCompliance->id,
            'title' => $title,
        ]);

        $sheqCompliance->delete();

        return back()->with('success', 'Document deleted successfully.');
    }

    /**
     * Move a SHEQ compliance document one position earlier in the list, swapping sort_order
     * with the immediately preceding document. No-op if already first.
     */
    public function moveUp(Media $sheqCompliance): RedirectResponse
    {
        abort_unless($sheqCompliance->collection === self::COLLECTION && $sheqCompliance->mediable_type === null, 404);

        $previous = Media::where('collection', self::COLLECTION)
            ->whereNull('mediable_type')
            ->where('sort_order', '<', $sheqCompliance->sort_order)
            ->orderByDesc('sort_order')
            ->first();

        if ($previous) {
            DB::transaction(function () use ($sheqCompliance, $previous): void {
                $currentSortOrder = $sheqCompliance->sort_order;
                $sheqCompliance->update(['sort_order' => $previous->sort_order]);
                $previous->update(['sort_order' => $currentSortOrder]);
            });
        }

        return back();
    }

    /**
     * Move a SHEQ compliance document one position later in the list, swapping sort_order
     * with the immediately following document. No-op if already last.
     */
    public function moveDown(Media $sheqCompliance): RedirectResponse
    {
        abort_unless($sheqCompliance->collection === self::COLLECTION && $sheqCompliance->mediable_type === null, 404);

        $next = Media::where('collection', self::COLLECTION)
            ->whereNull('mediable_type')
            ->where('sort_order', '>', $sheqCompliance->sort_order)
            ->orderBy('sort_order')
            ->first();

        if ($next) {
            DB::transaction(function () use ($sheqCompliance, $next): void {
                $currentSortOrder = $sheqCompliance->sort_order;
                $sheqCompliance->update(['sort_order' => $next->sort_order]);
                $next->update(['sort_order' => $currentSortOrder]);
            });
        }

        return back();
    }

    /**
     * Download a SHEQ compliance document file.
     */
    public function download(Request $request, Media $sheqCompliance)
    {
        abort_unless($sheqCompliance->collection === self::COLLECTION && $sheqCompliance->mediable_type === null, 404);
        abort_unless($this->companyUserService->canViewRestrictedModel($request->user(), $sheqCompliance), 403);

        $disk = Storage::disk($sheqCompliance->disk);

        if (! $disk->exists($sheqCompliance->path)) {
            abort(404, 'File not found.');
        }

        return $disk->download($sheqCompliance->path, $sheqCompliance->original_name);
    }

    /**
     * View a SHEQ compliance document file inline in the browser.
     */
    public function view(Request $request, Media $sheqCompliance)
    {
        abort_unless($sheqCompliance->collection === self::COLLECTION && $sheqCompliance->mediable_type === null, 404);
        abort_unless($this->companyUserService->canViewRestrictedModel($request->user(), $sheqCompliance), 403);

        $disk = Storage::disk($sheqCompliance->disk);

        if (! $disk->exists($sheqCompliance->path)) {
            abort(404, 'File not found.');
        }

        return $disk->response($sheqCompliance->path, $sheqCompliance->original_name, [
            'Content-Type' => $sheqCompliance->mime_type,
        ]);
    }
}

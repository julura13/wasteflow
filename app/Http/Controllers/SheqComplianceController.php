<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSheqComplianceDocumentRequest;
use App\Http\Requests\UpdateSheqComplianceDocumentRequest;
use App\Models\ActivityLog;
use App\Models\Media;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * SHEQ Compliance documents (Safety, Health, Environment & Quality / "HSE File").
 * Same shape and access rules as DocumentController, but standalone (not attached to
 * any Order/etc.) media rows filtered by collection = self::COLLECTION.
 */
class SheqComplianceController extends Controller
{
    private const COLLECTION = 'sheq_compliance';

    /**
     * Display a listing of SHEQ compliance documents, marking any unseen ones as seen.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        Media::query()
            ->where('collection', self::COLLECTION)
            ->whereNull('mediable_type')
            ->unseenByUser($user->id)
            ->get()
            ->each(fn (Media $media) => $media->markSeenBy($user));

        $documents = Media::where('collection', self::COLLECTION)
            ->whereNull('mediable_type')
            ->with('uploadedBy:id,name')
            ->latest()
            ->paginate(15)
            ->through(fn (Media $media) => [
                'id' => $media->id,
                'title' => $media->title,
                'description' => $media->description,
                'original_name' => $media->original_name,
                'human_readable_size' => $media->human_readable_size,
                'uploaded_by' => $media->uploadedBy?->name,
                'created_at' => $media->created_at->format('Y-m-d H:i'),
            ]);

        return Inertia::render('SheqCompliance/Index', [
            'documents' => $documents,
        ]);
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

        $document = Media::create([
            'collection' => self::COLLECTION,
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
     * Download a SHEQ compliance document file.
     */
    public function download(Media $sheqCompliance)
    {
        abort_unless($sheqCompliance->collection === self::COLLECTION && $sheqCompliance->mediable_type === null, 404);

        $disk = Storage::disk($sheqCompliance->disk);

        if (! $disk->exists($sheqCompliance->path)) {
            abort(404, 'File not found.');
        }

        return $disk->download($sheqCompliance->path, $sheqCompliance->original_name);
    }

    /**
     * View a SHEQ compliance document file inline in the browser.
     */
    public function view(Media $sheqCompliance)
    {
        abort_unless($sheqCompliance->collection === self::COLLECTION && $sheqCompliance->mediable_type === null, 404);

        $disk = Storage::disk($sheqCompliance->disk);

        if (! $disk->exists($sheqCompliance->path)) {
            abort(404, 'File not found.');
        }

        return $disk->response($sheqCompliance->path, $sheqCompliance->original_name, [
            'Content-Type' => $sheqCompliance->mime_type,
        ]);
    }
}

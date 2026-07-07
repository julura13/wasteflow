<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentRequest;
use App\Http\Requests\UpdateDocumentRequest;
use App\Models\ActivityLog;
use App\Models\Document;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class DocumentController extends Controller
{
    /**
     * Display a listing of documents, marking any unseen ones as seen for the current user.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        Document::query()
            ->unseenByUser($user->id)
            ->get()
            ->each(fn (Document $document) => $document->markSeenBy($user));

        $documents = Document::with('uploadedBy:id,name')
            ->latest()
            ->paginate(15)
            ->through(fn (Document $document) => [
                'id' => $document->id,
                'title' => $document->title,
                'description' => $document->description,
                'original_name' => $document->original_name,
                'human_readable_size' => $document->human_readable_size,
                'uploaded_by' => $document->uploadedBy?->name,
                'created_at' => $document->created_at->format('Y-m-d H:i'),
            ]);

        return Inertia::render('Documents/Index', [
            'documents' => $documents,
        ]);
    }

    /**
     * Store a newly uploaded document.
     */
    public function store(StoreDocumentRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        /** @var UploadedFile $file */
        $file = $request->file('file');
        $disk = config('filesystems.default');
        $fileName = Str::uuid().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs('documents', $fileName, $disk);

        $document = Document::create([
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

        ActivityLog::log('document_uploaded', "Document \"{$document->title}\" uploaded", $document, [
            'document_id' => $document->id,
            'title' => $document->title,
        ]);

        return back()->with('success', 'Document uploaded successfully.');
    }

    /**
     * Update an existing document's details, optionally replacing the file.
     */
    public function update(UpdateDocumentRequest $request, Document $document): RedirectResponse
    {
        $validated = $request->validated();

        $document->title = $validated['title'];
        $document->description = $validated['description'] ?? null;

        if ($request->hasFile('file')) {
            $oldDisk = Storage::disk($document->disk);
            if ($oldDisk->exists($document->path)) {
                $oldDisk->delete($document->path);
            }

            /** @var UploadedFile $file */
            $file = $request->file('file');
            $disk = config('filesystems.default');
            $fileName = Str::uuid().'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs('documents', $fileName, $disk);

            $document->original_name = $file->getClientOriginalName();
            $document->file_name = $fileName;
            $document->mime_type = $file->getMimeType();
            $document->disk = $disk;
            $document->path = $path;
            $document->file_size = $file->getSize();
        }

        $document->save();

        ActivityLog::log('document_updated', "Document \"{$document->title}\" updated", $document, [
            'document_id' => $document->id,
            'title' => $document->title,
        ]);

        return back()->with('success', 'Document updated successfully.');
    }

    /**
     * Delete a document.
     */
    public function destroy(Document $document): RedirectResponse
    {
        $title = $document->title;

        ActivityLog::log('document_deleted', "Document \"{$title}\" deleted", $document, [
            'document_id' => $document->id,
            'title' => $title,
        ]);

        $document->delete();

        return back()->with('success', 'Document deleted successfully.');
    }

    /**
     * Download a document file.
     */
    public function download(Document $document)
    {
        $disk = Storage::disk($document->disk);

        if (! $disk->exists($document->path)) {
            abort(404, 'File not found.');
        }

        return $disk->download($document->path, $document->original_name);
    }
}

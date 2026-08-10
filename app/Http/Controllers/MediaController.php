<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Media;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class MediaController extends Controller
{
    /**
     * Abort with 403 if the order does not belong to the acting user's company.
     * Mirrors OrderController::ensureOrderInScope() so media access follows the
     * same tenant-isolation rule as the order itself.
     */
    protected function ensureOrderInScope(Order $order): void
    {
        $user = auth()->user();
        if ($user && $user->company_id && (int) $order->company_id !== (int) $user->company_id) {
            abort(403, 'You do not have access to this order.');
        }
    }

    /**
     * Upload a file and associate it with an order.
     */
    public function upload(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|max:10240',
            'mediable_type' => 'required|string',
            'mediable_id' => 'required|integer|exists:orders,id',
            'collection' => 'nullable|string|in:default,supporting_documents',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validated['mediable_type'] !== 'App\\Models\\Order') {
            return back()->withErrors(['file' => 'Invalid media type.']);
        }

        $order = Order::findOrFail($validated['mediable_id']);
        $this->ensureOrderInScope($order);

        /** @var UploadedFile $file */
        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $mimeType = $file->getMimeType();
        $fileSize = $file->getSize();

        $collection = $validated['collection'] ?? 'default';
        $relativeDirectory = 'orders/'.$order->id.'/'.$collection;
        $fileName = Str::uuid().'.'.$file->getClientOriginalExtension();

        $shouldDualStore = $collection === 'supporting_documents'
            && in_array($order->status, ['documents_required', 'finalized'], true);

        $disk = $shouldDualStore ? 'wasabi' : config('filesystems.default');

        $path = null;
        $localDisk = null;
        $localPath = null;

        try {
            if ($shouldDualStore) {
                $localDisk = 'local';
                $localPath = $file->storeAs($relativeDirectory, $fileName, $localDisk);
            }

            $path = $file->storeAs($relativeDirectory, $fileName, $disk);

            if (! is_string($path) || $path === '') {
                throw new \RuntimeException("Failed to store upload to disk [{$disk}].");
            }
        } catch (Throwable $e) {
            if ($localDisk && $localPath && Storage::disk($localDisk)->exists($localPath)) {
                Storage::disk($localDisk)->delete($localPath);
            }

            throw $e;
        }

        $media = Media::create([
            'mediable_type' => $validated['mediable_type'],
            'mediable_id' => $order->id,
            'file_name' => $fileName,
            'original_name' => $originalName,
            'mime_type' => $mimeType,
            'disk' => $disk,
            'path' => $path,
            'local_disk' => $localDisk,
            'local_path' => $localPath,
            'local_cached_at' => $localPath ? now() : null,
            'file_size' => $fileSize,
            'collection' => $collection,
            'description' => $validated['description'] ?? null,
        ]);

        ActivityLog::log('media_uploaded', "Document \"{$originalName}\" uploaded to order {$order->tracking_number}", $order, [
            'order_id' => $order->id,
            'tracking_number' => $order->tracking_number,
            'media_id' => $media->id,
            'original_name' => $originalName,
        ]);

        return back()->with('success', 'File uploaded successfully.');
    }

    /**
     * Download a media file. This route is scoped to orders permissions only (see routes/web.php),
     * so any media not attached to an Order - e.g. standalone SHEQ Compliance documents, which have
     * their own manage-documents-gated routes - must never be reachable here.
     */
    public function download(Media $media)
    {
        abort_unless($media->mediable_type === 'App\\Models\\Order', 404, 'File not found.');

        $order = Order::find($media->mediable_id);
        abort_if(! $order, 404, 'File not found.');
        $this->ensureOrderInScope($order);

        $disk = Storage::disk($media->disk);

        if (! $disk->exists($media->path)) {
            abort(404, 'File not found.');
        }

        return $disk->download($media->path, $media->original_name);
    }

    /**
     * Delete a media file. This route is scoped to orders permissions only (see routes/web.php),
     * so any media not attached to an Order - e.g. standalone SHEQ Compliance documents, which have
     * their own manage-documents-gated routes - must never be reachable here.
     */
    public function destroy(Media $media)
    {
        abort_unless($media->mediable_type === 'App\\Models\\Order', 404, 'File not found.');

        $order = Order::find($media->mediable_id);
        abort_if(! $order, 404, 'File not found.');
        $this->ensureOrderInScope($order);
        $originalName = $media->original_name;

        $disk = Storage::disk($media->disk);

        try {
            $disk->delete($media->path);
        } catch (Throwable) {
            // If cloud storage is misconfigured, still allow DB record cleanup.
        }

        if ($media->local_disk && $media->local_path) {
            $localDisk = Storage::disk($media->local_disk);
            $localDisk->delete($media->local_path);
        }

        ActivityLog::log('media_deleted', "Document \"{$originalName}\" deleted from order {$order->tracking_number}", $order, [
            'media_id' => $media->id,
            'original_name' => $originalName,
            'order_id' => $media->mediable_id,
        ]);

        $media->delete();

        return back()->with('success', 'File deleted successfully.');
    }
}

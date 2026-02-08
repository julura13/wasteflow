<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    /**
     * Upload a file and associate it with an order.
     */
    public function upload(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|max:10240',
            'mediable_type' => 'required|string',
            'mediable_id' => 'required|integer|exists:orders,id',
            'collection' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validated['mediable_type'] !== 'App\\Models\\Order') {
            return back()->withErrors(['file' => 'Invalid media type.']);
        }

        $order = Order::findOrFail($validated['mediable_id']);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $mimeType = $file->getMimeType();
        $fileSize = $file->getSize();

        $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
        
        $disk = config('filesystems.default');
        
        $path = $file->storeAs(
            'orders/' . $order->id . '/' . ($validated['collection'] ?? 'default'),
            $fileName,
            $disk
        );

        $media = Media::create([
            'mediable_type' => $validated['mediable_type'],
            'mediable_id' => $order->id,
            'file_name' => $fileName,
            'original_name' => $originalName,
            'mime_type' => $mimeType,
            'disk' => $disk,
            'path' => $path,
            'file_size' => $fileSize,
            'collection' => $validated['collection'] ?? 'default',
            'description' => $validated['description'] ?? null,
        ]);

        return back()->with('success', 'File uploaded successfully.');
    }

    /**
     * Download a media file.
     */
    public function download(Media $media)
    {
        $disk = Storage::disk($media->disk);
        
        if (!$disk->exists($media->path)) {
            abort(404, 'File not found.');
        }

        return $disk->download($media->path, $media->original_name);
    }

    /**
     * Delete a media file.
     */
    public function destroy(Media $media)
    {
        $disk = Storage::disk($media->disk);
        
        if ($disk->exists($media->path)) {
            $disk->delete($media->path);
        }

        $media->delete();

        return back()->with('success', 'File deleted successfully.');
    }
}

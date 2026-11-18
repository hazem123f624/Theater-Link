<?php

namespace Modules\Tenancy\Services;

use Illuminate\Support\Facades\Storage;
use Modules\Tenancy\Models\GroupApplicationDocument;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GroupApplicationDocumentService
{
    public function download(GroupApplicationDocument $document, bool $inline = false): BinaryFileResponse|StreamedResponse
    {
        $media = $document->media;

        if (! $media) {
            abort(404, __('messages.resource_not_found'));
        }

        $disk = Storage::disk($media->disk ?? config('filesystems.default'));

        if (! $disk->exists($media->path)) {
            abort(404, __('messages.resource_not_found'));
        }

        $headers = [
            'Content-Type' => $media->mime_type,
            'Cache-Control' => 'private, no-store',
        ];

        if ($inline) {
            return response()->file($disk->path($media->path), $headers);
        }

        return $disk->download($media->path, $media->original_name, $headers);
    }
}

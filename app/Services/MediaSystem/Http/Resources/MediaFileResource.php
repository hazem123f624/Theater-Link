<?php

namespace App\Services\MediaSystem\Http\Resources;

use App\Services\MediaSystem\DTOs\MediaFileDto;
use App\Services\MediaSystem\Models\MediaFile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MediaFile
 */
class MediaFileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return MediaFileDto::fromModel($this->resource)->toArray();
    }
}

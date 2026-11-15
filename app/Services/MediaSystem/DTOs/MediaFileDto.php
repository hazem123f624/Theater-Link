<?php

namespace App\Services\MediaSystem\DTOs;

use App\DTOs\BaseDto;
use App\Services\MediaSystem\Models\MediaFile;

class MediaFileDto extends BaseDto
{
    public function __construct(
        public readonly int $id,
        public readonly ?int $mediaFolderId,
        public readonly ?string $originalName,
        public readonly string $path,
        public readonly string $url,
        public readonly string $extension,
        public readonly string $mimeType,
        public readonly ?int $size,
        public readonly string $sizeKb,
        public readonly string $sizeGb,
        public readonly ?string $category,
        public readonly string $visibility,
        public readonly ?string $disk,
        public readonly ?string $newName,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
    ) {}

    public static function fromModel($model): self
    {
        /** @var MediaFile $model */
        return new self(
            id: $model->id,
            mediaFolderId: $model->media_folder_id,
            originalName: $model->original_name,
            path: $model->path,
            url: $model->url,
            extension: $model->extension,
            mimeType: $model->mime_type,
            size: $model->size,
            sizeKb: $model->size
                ? number_format(round($model->size / 1024, 2), 2, '.', '')
                : '0.00',
            sizeGb: $model->size
                ? number_format(round($model->size / 1073741824, 6), 6, '.', '')
                : '0.000000',
            category: $model->category ?? null,
            visibility: $model->visibility,
            disk: $model->disk,
            newName: basename($model->path),
            createdAt: $model->created_at?->toISOString(),
            updatedAt: $model->updated_at?->toISOString(),
        );
    }

    public function toArray(): array
    {
        return [
            'id'              => $this->id,
            'media_folder_id' => $this->mediaFolderId,
            'original_name'   => $this->originalName,
            'new_name'        => $this->newName,
            'url'             => $this->url,
            'size'            => $this->size,
            'size_kb'         => $this->sizeKb,
            'size_gb'         => $this->sizeGb,
            'category'        => $this->category,
            'created_at'      => $this->createdAt,
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class MessageAttachmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fileName' => $this->file_name,
            'fileType' => $this->file_type,
            'mimeType' => $this->mime_type,
            'fileSize' => $this->file_size,
            'fileUrl' => Storage::url($this->file_path),
        ];
    }
}

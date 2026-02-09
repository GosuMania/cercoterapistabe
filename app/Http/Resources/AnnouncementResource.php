<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnnouncementResource extends JsonResource
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
            'center' => new CenterProfileResource($this->whenLoaded('center')),
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,
            'content' => $this->content,
            'contractType' => $this->contract_type,
            'weeklyHours' => $this->weekly_hours,
            'requirements' => $this->requirements,
            'isActive' => $this->is_active,
            'expiresAt' => $this->expires_at,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}

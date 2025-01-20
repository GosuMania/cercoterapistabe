<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CenterProfileResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'center_name' => $this->center_name,
            'therapies' => $this->therapies,
            'service' => $this->service,
            'description' => $this->description,
            'therapists' => TherapistProfileResource::collection($this->whenLoaded('therapists')), // Relazione con i terapeuti
        ];
    }
}



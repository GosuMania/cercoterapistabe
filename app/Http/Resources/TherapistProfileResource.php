<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TherapistProfileResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'profession' => $this->profession,
            'therapies' => $this->therapies,
            'bio' => $this->bio,
            'hourly_rate' => $this->hourly_rate,
            'centers' => CenterProfileResource::collection($this->whenLoaded('centers')), // Relazione con i centri
        ];
    }
}



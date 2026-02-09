<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CenterProfileResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'centerName' => $this->center_name,
            'partitaIva' => $this->partita_iva,
            'therapies' => $this->therapies,
            'service' => $this->service,
            'description' => $this->description,
            'logoUrl' => $this->logo_url,
            'averageRating' => $this->when(isset($this->average_rating), $this->average_rating),
            'therapists' => TherapistProfileResource::collection($this->whenLoaded('therapists')),
            'announcements' => AnnouncementResource::collection($this->whenLoaded('announcements')),
        ];
    }
}



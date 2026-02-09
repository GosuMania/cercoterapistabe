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
            'homeTherapy' => $this->home_therapy,
            'rangeHomeTherapy' => $this->range_home_therapy,
            'bio' => $this->bio,
            'hourlyRate' => $this->hourly_rate,
            'affiliationCenterId' => $this->affiliation_center_id,
            'affiliationCenter' => new CenterProfileResource($this->whenLoaded('affiliationCenter')),
            'yearsOfExperience' => $this->years_of_experience,
            'averageRating' => $this->when(isset($this->average_rating), $this->average_rating),
            'centers' => CenterProfileResource::collection($this->whenLoaded('centers')),
        ];
    }
}



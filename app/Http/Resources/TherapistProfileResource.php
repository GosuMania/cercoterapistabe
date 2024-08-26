<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TherapistProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'specialization' => $this->specialization,
            'bio' => $this->bio,
            'clinicAddress' => $this->clinic_address,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}


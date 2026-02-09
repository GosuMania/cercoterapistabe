<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocationResource extends JsonResource
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
            'label' => $this->label,
            'streetAddress' => $this->street_address,
            'city' => $this->city,
            'postalCode' => $this->postal_code,
            'countryCode' => $this->country_code,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'formattedAddress' => $this->formatted_address,
            'isDefault' => $this->is_default,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}

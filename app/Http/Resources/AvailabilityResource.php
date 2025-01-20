<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AvailabilityResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'day_of_week' => $this->day_of_week,
            'morning' => $this->morning,
            'afternoon' => $this->afternoon,
            'evening' => $this->evening,
        ];
    }
}

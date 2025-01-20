<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ParentPatientProfileResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'relationship' => $this->relationship,
            'therapies' => $this->therapies, // Restituisce array
        ];
    }
}


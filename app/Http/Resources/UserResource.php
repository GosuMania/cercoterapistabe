<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'type' => $this->type,
            'isPremium' => $this->is_premium,
            'firebaseToken' => $this->firebase_token,
            'profile' => $this->getProfile(),
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }

    /**
     * Get the specific profile based on the user type.
     *
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    protected function getProfile()
    {
        switch ($this->type) {
            case 'therapist':
                return new TherapistProfileResource($this->therapistProfile);
            case 'parent_patient':
                return new ParentPatientProfileResource($this->parentPatientProfile);
            case 'center':
                return new CenterProfileResource($this->centerProfile);
            default:
                return null;
        }
    }
}

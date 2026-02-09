<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'surname' => $this->surname,
            'email' => $this->email,
            'imageUrl' => $this->image_url,
            'firebaseToken' => $this->firebase_token,
            'type' => $this->type,
            'isPremium' => $this->is_premium,
            'onboardingCompleted' => $this->onboarding_completed,
            'emailVerifiedAt' => $this->email_verified_at,
            'profile' => $this->getProfile(),
            'availabilities' => AvailabilityResource::collection($this->whenLoaded('availabilities')),
            'locations' => LocationResource::collection($this->whenLoaded('locations')),
            'distance' => $this->when(isset($this->distance), round($this->distance, 2)), // Distanza in km per risultati ricerca
        ];
    }

    /**
     * Get the profile based on user type.
     *
     * @return mixed
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

<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ConversationUserResource extends JsonResource
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
            'conversationId' => $this->conversation_id,
            'userId' => $this->user_id,
            'conversation' => new ConversationResource($this->whenLoaded('conversation')),
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}

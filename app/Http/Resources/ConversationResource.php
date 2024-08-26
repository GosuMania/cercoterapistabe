<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
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
            'conversationName' => $this->conversation_name,
            'users' => UserResource::collection($this->whenLoaded('users')),
            'messages' => MessageResource::collection($this->whenLoaded('messages')),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
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
            'conversationId' => $this->conversation_id,
            'senderId' => $this->sender_id,
            'messageContent' => $this->message_content,
            'sender' => new UserResource($this->whenLoaded('sender')),
            'attachments' => MessageAttachmentResource::collection($this->whenLoaded('attachments')),
            'sentAt' => $this->sent_at,
            'deliveredAt' => $this->delivered_at,
            'readAt' => $this->read_at,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}

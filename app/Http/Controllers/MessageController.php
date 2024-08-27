<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Http\Resources\MessageResource;
use App\Models\Message;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Kreait\Firebase\Contract\Messaging as FirebaseMessaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class MessageController extends Controller
{
    protected $messaging;

    public function __construct(FirebaseMessaging $messaging)
    {
        $this->messaging = $messaging;
    }

    public function store(Request $request, $conversationId)
    {
        // Validazione dell'input
        $validatedData = $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        // Controlla se l'utente è un partecipante della conversazione
        $conversation = Conversation::findOrFail($conversationId);
        if (!$conversation->users->contains(auth()->id())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Creazione del messaggio
        $message = Message::create([
            'conversation_id' => $conversationId,
            'sender_id' => auth()->id(),
            'message_content' => $validatedData['content'],
        ]);

        // Invia la notifica push ai partecipanti della conversazione
        $this->sendPushNotification($message);

        // Emetti l'evento per il real-time messaging
        broadcast(new MessageSent($message))->toOthers();

        return new MessageResource($message->load('sender'));
    }

    protected function sendPushNotification(Message $message)
    {
        $conversation = $message->conversation;
        $participants = $conversation->users()->where('id', '!=', $message->sender_id)->get();

        foreach ($participants as $participant) {
            if ($participant->firebase_token) { // Controlla se il token Firebase è disponibile
                $notification = Notification::create('Nuovo Messaggio', $message->message_content);
                $cloudMessage = CloudMessage::withTarget('token', $participant->firebase_token)
                    ->withNotification($notification)
                    ->withData(['conversationId' => $message->conversation_id]);

                $this->messaging->send($cloudMessage);
            }
        }
    }
}

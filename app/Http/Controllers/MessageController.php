<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Http\Resources\MessageResource;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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

    public function index($conversationId)
    {
        $conversation = Conversation::findOrFail($conversationId);
        $userId = auth()->id();

        if (!$conversation->users->contains($userId)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $perPage = request()->validate([
            'perPage' => 'integer|min:1|max:100',
        ])['perPage'] ?? 20;

        $messages = Message::where('conversation_id', $conversationId)
            ->with(['sender', 'attachments'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return MessageResource::collection($messages);
    }

    public function show($id)
    {
        $message = Message::with(['sender', 'attachments'])->findOrFail($id);
        $userId = auth()->id();

        $conversation = $message->conversation;
        if (!$conversation->users->contains($userId)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return new MessageResource($message);
    }

    public function update(Request $request, $id)
    {
        $message = Message::findOrFail($id);
        $userId = auth()->id();

        if ($message->sender_id !== $userId) {
            return response()->json(['error' => 'Non autorizzato'], 403);
        }

        $validated = $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $message->update([
            'message_content' => $validated['content'],
        ]);

        return new MessageResource($message->load(['sender', 'attachments']));
    }

    public function destroy($id)
    {
        $message = Message::findOrFail($id);
        $userId = auth()->id();

        if ($message->sender_id !== $userId) {
            return response()->json(['error' => 'Non autorizzato'], 403);
        }

        $message->delete();

        return response()->noContent();
    }

    public function store(Request $request, $conversationId)
    {
        // Validazione dell'input
        $validatedData = $request->validate([
            'content' => 'required|string|max:1000',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|mimes:pdf,jpeg,jpg,png|max:10240', // Max 10MB
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
            'sent_at' => now(),
        ]);

        // Gestione allegati
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $filePath = $file->store('message_attachments', 'public');
                $fileType = in_array($file->getMimeType(), ['application/pdf']) ? 'pdf' : 'image';

                MessageAttachment::create([
                    'message_id' => $message->id,
                    'file_path' => $filePath,
                    'file_name' => $file->getClientOriginalName(),
                    'file_type' => $fileType,
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                ]);
            }
        }

        // Invia la notifica push ai partecipanti della conversazione
        $this->sendPushNotification($message);

        // Marca come consegnato per gli altri partecipanti
        $participants = $conversation->users()->where('users.id', '!=', auth()->id())->get();
        foreach ($participants as $participant) {
            // Il messaggio è considerato consegnato quando viene inviato
            // read_at verrà aggiornato quando l'utente legge il messaggio
        }

        return new MessageResource($message->load(['sender', 'attachments']));
    }

    /**
     * Marca un messaggio come letto
     */
    public function markAsRead($id)
    {
        $message = Message::findOrFail($id);
        $userId = auth()->id();

        // Verifica che l'utente sia un partecipante della conversazione
        $conversation = $message->conversation;
        if (!$conversation->users->contains($userId)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Solo se non è il mittente
        if ($message->sender_id !== $userId && !$message->read_at) {
            $message->update([
                'read_at' => now(),
            ]);

            // Se non c'era delivered_at, lo imposta
            if (!$message->delivered_at) {
                $message->update(['delivered_at' => now()]);
            }
        }

        return new MessageResource($message->load(['sender', 'attachments']));
    }

    protected function sendPushNotification(Message $message)
    {
        $conversation = $message->conversation;
        $participants = $conversation->users()->where('users.id', '!=', $message->sender_id)->get();

        foreach ($participants as $participant) {
            if (!empty($participant->firebase_token)) { // Controlla se il token Firebase è disponibile e non nullo
                $notification = Notification::create('Nuovo Messaggio', $message->message_content);
                $cloudMessage = CloudMessage::withTarget('token', $participant->firebase_token)
                    ->withNotification($notification)
                    ->withData(['conversationId' => $message->conversation_id]);

                try {
                    $this->messaging->send($cloudMessage);
                } catch (\Exception $e) {
                    \Log::error('Errore nell\'invio della notifica Firebase: ' . $e->getMessage());
                }
            }
        }
    }
}

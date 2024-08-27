<?php

namespace App\Http\Controllers;

use App\Http\Resources\ConversationResource;
use App\Models\Conversation;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    /**
     * Recupera tutte le conversazioni di un utente specifico.
     *
     * @param int $userId
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getByAuthenticatedUser()
    {
        $userId = auth()->id();  // Ottiene l'ID dell'utente autenticato

        $conversations = Conversation::whereHas('users', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->with(['users', 'messages.sender'])->get();

        return ConversationResource::collection($conversations);
    }

    public function index()
    {
        $conversations = Conversation::with(['users', 'messages.sender'])->get();
        return ConversationResource::collection($conversations);
    }

    public function show($conversationId)
    {
        // Verifica che l'utente autenticato faccia parte della conversazione
        $userId = auth()->id();
        $conversation = Conversation::where('id', $conversationId)
            ->whereHas('users', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->firstOrFail();

        // Recupera i messaggi della conversazione
        $messages = Message::where('conversation_id', $conversationId)
            ->with('sender')
            ->paginate(request('perPage', 20));

        return MessageResource::collection($messages);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id', // ID dell'utente con cui si vuole avviare la conversazione
        ]);

        $authUserId = auth()->id();
        $otherUserId = $validatedData['user_id'];

        // Cerca una conversazione esistente tra i due utenti
        $conversation = Conversation::whereHas('users', function ($query) use ($authUserId) {
            $query->where('user_id', $authUserId);
        })->whereHas('users', function ($query) use ($otherUserId) {
            $query->where('user_id', $otherUserId);
        })->first();

        // Se non esiste, crea una nuova conversazione
        if (!$conversation) {
            $conversationName = 'Conversation between ' . $authUserId . ' and ' . $otherUserId;

            $conversation = Conversation::create([
                'conversation_name' => $conversationName,
            ]);

            // Associa entrambi gli utenti alla conversazione
            $conversation->users()->attach([$authUserId, $otherUserId]);
        }

        return new ConversationResource($conversation->load(['users', 'messages.sender']));
    }



    public function update(Request $request, $id)
    {
        $conversation = Conversation::findOrFail($id);

        // Assicurati che solo il creatore possa aggiornare la conversazione
        $this->authorize('update', $conversation);

        $validatedData = $request->validate([
            'conversation_name' => 'required|string|max:255',
        ]);

        $conversation->update($validatedData);

        return new ConversationResource($conversation->load(['users', 'messages.sender']));
    }

    public function destroy($id)
    {
        $conversation = Conversation::findOrFail($id);

        // Assicurati che solo il creatore possa cancellare la conversazione
        $this->authorize('delete', $conversation);

        $conversation->delete();

        return response()->noContent();
    }
}

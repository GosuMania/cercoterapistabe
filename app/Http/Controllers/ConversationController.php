<?php

namespace App\Http\Controllers;

use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource; // Import necessario
use App\Models\Conversation;
use App\Models\Message;
use App\Models\UserInteraction;
use App\Models\User;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function getByAuthenticatedUser()
    {
        $userId = auth()->id();

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
        $userId = auth()->id();
        $conversation = Conversation::where('id', $conversationId)
            ->whereHas('users', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->firstOrFail();

        // Recupera i messaggi della conversazione
        $perPage = request()->validate([
            'perPage' => 'integer|min:1|max:100',
        ])['perPage'] ?? 20;

        $messages = Message::where('conversation_id', $conversationId)
            ->with('sender')
            ->paginate($perPage);

        return MessageResource::collection($messages);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $authUser = auth()->user();
        $authUserId = $authUser->id;
        $otherUserId = $validatedData['user_id'];
        $otherUser = User::findOrFail($otherUserId);

        // Filtro anti-spam: i Centri possono contattare un Genitore solo se il Genitore ha interagito prima
        if ($authUser->type === 'center' && $otherUser->type === 'parent_patient') {
            $hasInteraction = UserInteraction::where('viewer_id', $otherUserId)
                ->where('viewed_id', $authUserId)
                ->whereIn('interaction_type', ['profile_view', 'info_request', 'search_result'])
                ->exists();

            if (!$hasInteraction) {
                return response()->json([
                    'error' => 'I centri possono contattare un genitore solo dopo che il genitore ha visualizzato il profilo o richiesto informazioni'
                ], 403);
            }
        }

        $conversation = Conversation::whereHas('users', function ($query) use ($authUserId) {
            $query->where('user_id', $authUserId);
        })->whereHas('users', function ($query) use ($otherUserId) {
            $query->where('user_id', $otherUserId);
        })->first();

        if (!$conversation) {
            $conversationName = 'Conversation between ' . $authUserId . ' and ' . $otherUserId;

            $conversation = Conversation::create([
                'conversation_name' => $conversationName,
            ]);

            $conversation->users()->attach([$authUserId, $otherUserId]);
        }

        return new ConversationResource($conversation->load(['users', 'messages.sender']));
    }

    public function update(Request $request, $conversationId)
    {
        $userId = auth()->id();
        $conversation = Conversation::where('id', $conversationId)
            ->whereHas('users', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->firstOrFail();

        $validatedData = $request->validate([
            'conversation_name' => 'required|string|max:255',
        ]);

        $conversation->update($validatedData);

        return new ConversationResource($conversation->load(['users', 'messages.sender']));
    }

    public function destroy($conversationId)
    {
        $userId = auth()->id();
        $conversation = Conversation::where('id', $conversationId)
            ->whereHas('users', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->firstOrFail();

        $conversation->delete();

        return response()->noContent();
    }
}

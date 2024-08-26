<?php

namespace App\Http\Controllers;

use App\Http\Resources\ConversationResource;
use App\Models\Conversation;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function index()
    {
        $conversations = Conversation::with(['participants', 'messages.sender'])->get();
        return ConversationResource::collection($conversations);
    }

    public function show($id)
    {
        $conversation = Conversation::with(['participants', 'messages.sender'])->findOrFail($id);
        return new ConversationResource($conversation);
    }

    public function store(Request $request)
    {
        $conversation = Conversation::create([
            'name' => $request->input('name'),
            'creator_id' => auth()->id(),
        ]);

        $conversation->participants()->attach(auth()->id());

        return new ConversationResource($conversation->load(['participants', 'messages.sender']));
    }

    public function update(Request $request, $id)
    {
        $conversation = Conversation::findOrFail($id);
        $conversation->update($request->only('name'));
        return new ConversationResource($conversation->load(['participants', 'messages.sender']));
    }

    public function destroy($id)
    {
        $conversation = Conversation::findOrFail($id);
        $conversation->delete();
        return response()->noContent();
    }
}



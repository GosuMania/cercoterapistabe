<?php

namespace App\Http\Controllers;

use App\Http\Resources\ReviewResource;
use App\Models\Review;
use App\Models\User;
use App\Models\TherapistProfile;
use App\Models\CenterProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    /**
     * Lista recensioni di un utente (therapist o center)
     */
    public function index(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($request->user_id);
        
        // Determina il tipo di reviewable
        $reviewable = null;
        if ($user->type === 'therapist' && $user->therapistProfile) {
            $reviewable = $user->therapistProfile;
        } elseif ($user->type === 'center' && $user->centerProfile) {
            $reviewable = $user->centerProfile;
        }

        if (!$reviewable) {
            return response()->json(['message' => 'Utente non recensibile'], 404);
        }

        $reviews = Review::where('reviewable_id', $reviewable->id)
            ->where('reviewable_type', get_class($reviewable))
            ->approved()
            ->with('reviewer')
            ->orderBy('created_at', 'desc')
            ->get();

        return ReviewResource::collection($reviews);
    }

    /**
     * Crea una nuova recensione (solo genitori)
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        // Solo genitori possono recensire
        if ($user->type !== 'parent_patient') {
            return response()->json(['error' => 'Solo i genitori possono lasciare recensioni'], 403);
        }

        $validated = $request->validate([
            'reviewed_user_id' => 'required|exists:users,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $reviewedUser = User::findOrFail($validated['reviewed_user_id']);

        // Determina il reviewable
        $reviewable = null;
        if ($reviewedUser->type === 'therapist' && $reviewedUser->therapistProfile) {
            $reviewable = $reviewedUser->therapistProfile;
        } elseif ($reviewedUser->type === 'center' && $reviewedUser->centerProfile) {
            $reviewable = $reviewedUser->centerProfile;
        } else {
            return response()->json(['error' => 'Utente non recensibile'], 400);
        }

        // Verifica se esiste già una recensione
        // Usa il nome completo della classe per il confronto
        $reviewableType = get_class($reviewable);
        $existingReview = Review::where('reviewer_id', $user->id)
            ->where('reviewable_id', $reviewable->id)
            ->where('reviewable_type', $reviewableType)
            ->first();

        if ($existingReview) {
            return response()->json(['error' => 'Hai già recensito questo utente'], 409);
        }

        $review = Review::create([
            'reviewer_id' => $user->id,
            'reviewable_id' => $reviewable->id,
            'reviewable_type' => $reviewableType,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
            'status' => 'approved',
        ]);

        return new ReviewResource($review->load('reviewer'));
    }

    /**
     * Mostra una recensione specifica
     */
    public function show($id)
    {
        $review = Review::with('reviewer')->findOrFail($id);
        return new ReviewResource($review);
    }

    /**
     * Risposta a una recensione (solo terapista/centro recensito)
     */
    public function respond(Request $request, $id)
    {
        $review = Review::findOrFail($id);
        $user = auth()->user();

        // Verifica che l'utente sia il recensito
        $isOwner = false;
        if ($review->reviewable_type === TherapistProfile::class && $user->therapistProfile) {
            $isOwner = $review->reviewable_id === $user->therapistProfile->id;
        } elseif ($review->reviewable_type === CenterProfile::class && $user->centerProfile) {
            $isOwner = $review->reviewable_id === $user->centerProfile->id;
        }

        if (!$isOwner) {
            return response()->json(['error' => 'Non autorizzato'], 403);
        }

        $validated = $request->validate([
            'response' => 'required|string|max:1000',
        ]);

        $review->update([
            'response' => $validated['response'],
            'response_at' => now(),
        ]);

        return new ReviewResource($review->load('reviewer'));
    }

    /**
     * Segnala una recensione
     */
    public function report($id)
    {
        $review = Review::findOrFail($id);
        $user = auth()->user();

        // Solo il recensito può segnalare
        $isOwner = false;
        if ($review->reviewable_type === TherapistProfile::class && $user->therapistProfile) {
            $isOwner = $review->reviewable_id === $user->therapistProfile->id;
        } elseif ($review->reviewable_type === CenterProfile::class && $user->centerProfile) {
            $isOwner = $review->reviewable_id === $user->centerProfile->id;
        }

        if (!$isOwner) {
            return response()->json(['error' => 'Non autorizzato'], 403);
        }

        $review->update([
            'status' => 'reported',
            'reported_at' => now(),
        ]);

        return response()->json(['message' => 'Recensione segnalata con successo']);
    }

    /**
     * Elimina una recensione (solo il recensore)
     */
    public function destroy($id)
    {
        $review = Review::findOrFail($id);
        $user = auth()->user();

        if ($review->reviewer_id !== $user->id) {
            return response()->json(['error' => 'Non autorizzato'], 403);
        }

        $review->delete();

        return response()->noContent();
    }
}

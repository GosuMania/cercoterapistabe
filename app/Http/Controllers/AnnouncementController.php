<?php

namespace App\Http\Controllers;

use App\Http\Resources\AnnouncementResource;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Http\Request;
use Kreait\Firebase\Contract\Messaging as FirebaseMessaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class AnnouncementController extends Controller
{
    protected $messaging;

    public function __construct(FirebaseMessaging $messaging)
    {
        $this->messaging = $messaging;
    }

    /**
     * Lista annunci con filtri
     */
    public function index(Request $request)
    {
        $query = Announcement::query()->active();

        // Filtri
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('center_id')) {
            $query->where('center_id', $request->center_id);
        }

        if ($request->has('contract_type')) {
            $query->where('contract_type', $request->contract_type);
        }

        // Per terapisti: solo annunci recruiting
        $user = auth()->user();
        if ($user && $user->type === 'therapist') {
            $query->recruiting();
        }

        $announcements = $query->with('center.user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return AnnouncementResource::collection($announcements);
    }

    /**
     * Annunci recruiting per terapisti (con notifiche)
     */
    public function recruiting(Request $request)
    {
        $query = Announcement::query()
            ->active()
            ->recruiting();

        // Filtri per terapisti
        if ($request->has('contract_type')) {
            $query->where('contract_type', $request->contract_type);
        }

        $announcements = $query->with('center.user')
            ->orderBy('created_at', 'desc')
            ->get();

        return AnnouncementResource::collection($announcements);
    }

    /**
     * Crea un nuovo annuncio (solo centri)
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        if ($user->type !== 'center' || !$user->centerProfile) {
            return response()->json(['error' => 'Solo i centri possono creare annunci'], 403);
        }

        $validated = $request->validate([
            'type' => 'required|in:recruiting,promotional',
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'content' => 'nullable|string',
            'contract_type' => 'required_if:type,recruiting|string|max:255',
            'weekly_hours' => 'required_if:type,recruiting|integer|min:1',
            'requirements' => 'nullable|array',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $announcement = Announcement::create([
            'center_id' => $user->centerProfile->id,
            'type' => $validated['type'],
            'title' => $validated['title'],
            'description' => $validated['description'],
            'content' => $validated['content'] ?? null,
            'contract_type' => $validated['contract_type'] ?? null,
            'weekly_hours' => $validated['weekly_hours'] ?? null,
            'requirements' => $validated['requirements'] ?? null,
            'is_active' => true,
            'expires_at' => $validated['expires_at'] ?? null,
        ]);

        // Se è un annuncio recruiting, invia notifiche push ai terapisti
        if ($announcement->type === 'recruiting') {
            $this->notifyTherapists($announcement);
        }

        return new AnnouncementResource($announcement->load('center.user'));
    }

    /**
     * Mostra un annuncio specifico
     */
    public function show($id)
    {
        $announcement = Announcement::with('center.user')->findOrFail($id);
        return new AnnouncementResource($announcement);
    }

    /**
     * Aggiorna un annuncio (solo il centro proprietario)
     */
    public function update(Request $request, $id)
    {
        $announcement = Announcement::findOrFail($id);
        $user = auth()->user();

        if ($user->type !== 'center' || !$user->centerProfile || $announcement->center_id !== $user->centerProfile->id) {
            return response()->json(['error' => 'Non autorizzato'], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:1000',
            'content' => 'nullable|string',
            'contract_type' => 'sometimes|string|max:255',
            'weekly_hours' => 'sometimes|integer|min:1',
            'requirements' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $announcement->update($validated);

        return new AnnouncementResource($announcement->load('center.user'));
    }

    /**
     * Elimina un annuncio (solo il centro proprietario)
     */
    public function destroy($id)
    {
        $announcement = Announcement::findOrFail($id);
        $user = auth()->user();

        if ($user->type !== 'center' || !$user->centerProfile || $announcement->center_id !== $user->centerProfile->id) {
            return response()->json(['error' => 'Non autorizzato'], 403);
        }

        $announcement->delete();

        return response()->noContent();
    }

    /**
     * Notifica i terapisti per un nuovo annuncio recruiting
     */
    protected function notifyTherapists(Announcement $announcement)
    {
        // Recupera terapisti che potrebbero essere interessati
        // (basato su competenze, vicinanza geografica, etc.)
        $therapists = User::where('type', 'therapist')
            ->whereNotNull('firebase_token')
            ->get();

        foreach ($therapists as $therapist) {
            if (!empty($therapist->firebase_token)) {
                $notification = Notification::create(
                    'Nuova opportunità lavorativa',
                    "Nuovo annuncio: {$announcement->title}"
                );
                $cloudMessage = CloudMessage::withTarget('token', $therapist->firebase_token)
                    ->withNotification($notification)
                    ->withData([
                        'type' => 'announcement',
                        'announcement_id' => $announcement->id,
                        'center_id' => $announcement->center_id,
                    ]);

                try {
                    $this->messaging->send($cloudMessage);
                } catch (\Exception $e) {
                    \Log::error('Errore nell\'invio della notifica Firebase: ' . $e->getMessage());
                }
            }
        }
    }
}

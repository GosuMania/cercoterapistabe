<?php

namespace App\Http\Controllers;

use App\Http\Resources\SavedUserResource;
use App\Http\Resources\UserResource;
use App\Models\SavedUser;
use App\Models\User;
use App\Models\Location;
use App\Models\UserInteraction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
public function index()
{
    $users = User::select('id', 'name', 'surname', 'image_url', 'type') // Seleziona i campi specifici
        ->with([
            'therapistProfile',
            'parentPatientProfile',
            'centerProfile',
            'availabilities'
        ])
        ->get();
    return UserResource::collection($users);
}


    public function getSavedUsers()
    {
        $userId = auth()->id();

        // Recupera i SavedUser con la relazione 'savedUser' per l'utente autenticato
        $savedUsers = SavedUser::where('user_id', $userId)
            ->with('savedUser') // Carica la relazione savedUser
            ->get();

        // Restituisce la collezione come resource
        return SavedUserResource::collection($savedUsers);
    }

    public function toggleSavedUser(Request $request)
    {
        // Validazione dei dati in input
        $validated = $request->validate([
            'savedUserId' => 'required|exists:users,id', // Verifica che l'ID esista nella tabella `users`
            'toggle' => 'required|boolean', // Booleano per decidere se salvare o rimuovere
        ]);

        $userId = auth()->id(); // ID dell'utente autenticato

        // Controlla se esiste già un record con questi valori
        $existingRecord = SavedUser::where('user_id', $userId)
            ->where('saved_user_id', $validated['savedUserId'])
            ->first();

        if ($validated['toggle']) {
            // Logica per salvare
            if ($existingRecord) {
                return response()->json([
                    'message' => 'Utente già salvato.'
                ], 409); // Conflict
            }

            $savedUser = SavedUser::create([
                'user_id' => $userId,
                'saved_user_id' => $validated['savedUserId'],
            ]);

            return new SavedUserResource($savedUser);
        } else {
            // Logica per rimuovere
            if (!$existingRecord) {
                return response()->json([
                    'message' => 'Utente non trovato tra quelli salvati.'
                ], 404); // Not Found
            }

            $existingRecord->delete();

            return response()->json([
                'message' => 'Utente rimosso dai salvati con successo.'
            ], 200); // Success
        }
    }

    public function show($id)
    {
        $user = User::with(['therapistProfile', 'parentPatientProfile', 'centerProfile'])->findOrFail($id);
        
        // Traccia interazione: visualizzazione profilo
        $authUser = auth()->user();
        if ($authUser && $authUser->id != $id) {
            \App\Models\UserInteraction::firstOrCreate([
                'viewer_id' => $authUser->id,
                'viewed_id' => $id,
                'interaction_type' => 'profile_view',
            ]);
        }
        
        return new UserResource($user);
    }

    public function getInfoUser()
    {
        $userId = auth()->id();
        $user = User::with(['therapistProfile', 'parentPatientProfile', 'centerProfile'])->findOrFail($userId);
        return new UserResource($user);
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        // Validazione
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'image_url' => 'nullable|url',
            'type' => 'required|in:therapist,parent_patient,center',
            'is_premium' => 'nullable|boolean',
            'therapies' => 'nullable|array', // JSON
            'profession' => 'nullable|string|max:255', // therapist
            'home_therapy' => 'nullable|boolean',      // therapist
            'range_home_therapy' => 'nullable|integer', // therapist
            'bio' => 'nullable|string|max:500',       // therapist
            'hourly_rate' => 'nullable|numeric',      // therapist
            'affiliation_center_id' => 'nullable|exists:center_profiles,id', // therapist
            'years_of_experience' => 'nullable|integer|min:0', // therapist
            'relationship' => 'nullable|string|max:255', // parent_patient
            'center_name' => 'nullable|string|max:255',  // center
            'partita_iva' => 'nullable|string|max:20',  // center
            'logo_url' => 'nullable|url',  // center
            'service' => 'nullable|array',               // JSON
            'description' => 'nullable|string|max:500',  // center
        ]);

        // Aggiorna i dati generici dell'utente
        $user->update([
            'name' => $data['name'],
            'surname' => $data['surname'],
            'image_url' => $data['image_url'] ?? $user->image_url,
            'type' => $data['type'],
            'is_premium' => $data['is_premium'] ?? $user->is_premium,
        ]);

        // Aggiornamento del profilo specifico
        switch ($user->type) {
            case 'therapist':
                $user->therapistProfile()->updateOrCreate(
                    ['user_id' => $user->id],
                    $request->only(['therapies', 'profession', 'home_therapy', 'range_home_therapy', 'bio', 'hourly_rate', 'affiliation_center_id', 'years_of_experience'])
                );
                break;
            case 'parent_patient':
                $user->parentPatientProfile()->updateOrCreate(
                    ['user_id' => $user->id],
                    $request->only(['therapies', 'relationship'])
                );
                break;
            case 'center':
                $user->centerProfile()->updateOrCreate(
                    ['user_id' => $user->id],
                    $request->only(['therapies', 'center_name', 'partita_iva', 'logo_url', 'service', 'description'])
                );
                break;
        }

        return response()->json([
            'message' => 'Profilo aggiornato con successo!',
            'user' => new \App\Http\Resources\UserResource($user->load(['therapistProfile', 'parentPatientProfile', 'centerProfile']))
        ]);
    }

    public function search(Request $request)
    {
        $data = $request->validate([
            'type' => 'nullable|in:therapist,parent_patient,center',
            'therapies' => 'nullable|array',
            'profession' => 'nullable|string',
            'service' => 'nullable|string',
            'is_premium' => 'nullable|boolean',
            // Nuovi filtri
            'home_therapy' => 'nullable|boolean',
            'min_hourly_rate' => 'nullable|numeric|min:0',
            'max_hourly_rate' => 'nullable|numeric|min:0',
            'min_rating' => 'nullable|numeric|min:1|max:5',
            'min_years_experience' => 'nullable|integer|min:0',
            'contract_type' => 'nullable|string', // Per ricerca centri con posizioni aperte
            // Geolocalizzazione
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'radius' => 'nullable|integer|min:1|max:50000', // in metri, max 50km
        ]);

        $query = User::query();

        if (isset($data['type'])) {
            $query->where('type', $data['type']);
        }

        // Filtri per terapisti
        if (isset($data['therapies'])) {
            $query->whereHas('therapistProfile', function ($q) use ($data) {
                foreach ($data['therapies'] as $therapy) {
                    $q->whereJsonContains('therapies', $therapy);
                }
            });
        }

        if (isset($data['profession'])) {
            $query->whereHas('therapistProfile', function ($q) use ($data) {
                $q->where('profession', 'like', '%' . $data['profession'] . '%');
            });
        }

        if (isset($data['home_therapy'])) {
            $query->whereHas('therapistProfile', function ($q) use ($data) {
                $q->where('home_therapy', $data['home_therapy']);
            });
        }

        if (isset($data['min_hourly_rate']) || isset($data['max_hourly_rate'])) {
            $query->whereHas('therapistProfile', function ($q) use ($data) {
                if (isset($data['min_hourly_rate'])) {
                    $q->where('hourly_rate', '>=', $data['min_hourly_rate']);
                }
                if (isset($data['max_hourly_rate'])) {
                    $q->where('hourly_rate', '<=', $data['max_hourly_rate']);
                }
            });
        }

        if (isset($data['min_years_experience'])) {
            $query->whereHas('therapistProfile', function ($q) use ($data) {
                $q->where('years_of_experience', '>=', $data['min_years_experience']);
            });
        }

        // Filtri per centri
        if (isset($data['service'])) {
            $query->whereHas('centerProfile', function ($q) use ($data) {
                $q->whereJsonContains('service', $data['service']);
            });
        }

        if (isset($data['contract_type'])) {
            // Cerca centri con annunci recruiting attivi con quel tipo di contratto
            $query->whereHas('centerProfile.announcements', function ($q) use ($data) {
                $q->where('type', 'recruiting')
                  ->where('contract_type', $data['contract_type'])
                  ->where('is_active', true)
                  ->where(function ($subQ) {
                      $subQ->whereNull('expires_at')
                           ->orWhere('expires_at', '>', now());
                  });
            });
        }

        if (isset($data['is_premium'])) {
            $query->where('is_premium', $data['is_premium']);
        }

        // Filtro per valutazione media (applicato dopo il caricamento)
        // Questo filtro verrà applicato in memoria dopo aver caricato gli utenti

        // Carica le relazioni necessarie per i filtri
        $users = $query->with([
            'therapistProfile' => function($q) {
                $q->with('reviews');
            },
            'centerProfile' => function($q) {
                $q->with('reviews');
            },
            'parentPatientProfile',
            'locations'
        ])->get();

        // Filtro per valutazione media (applicato in memoria)
        if (isset($data['min_rating'])) {
            $users = $users->filter(function ($user) use ($data) {
                $averageRating = 0;
                if ($user->type === 'therapist' && $user->therapistProfile) {
                    $averageRating = $user->therapistProfile->average_rating ?? 0;
                } elseif ($user->type === 'center' && $user->centerProfile) {
                    $averageRating = $user->centerProfile->average_rating ?? 0;
                }
                return $averageRating >= $data['min_rating'];
            });
        }

        // Geolocalizzazione: ordina per distanza se fornite coordinate
        if (isset($data['latitude']) && isset($data['longitude'])) {
            $radius = $data['radius'] ?? 50000; // Default 50km

            $users = $users->map(function ($user) use ($data, $radius) {
                $defaultLocation = $user->locations()->where('is_default', true)->first();
                
                if ($defaultLocation) {
                    // Calcola distanza usando formula Haversine
                    $distance = $this->calculateDistance(
                        $data['latitude'],
                        $data['longitude'],
                        $defaultLocation->latitude,
                        $defaultLocation->longitude
                    );
                    
                    // Filtra per raggio
                    if ($distance <= $radius) {
                        $user->distance = round($distance / 1000, 2); // in km
                        return $user;
                    }
                    return null;
                }
                return null;
            })->filter();
            
            // Ordina per distanza
            $users = $users->sortBy('distance')->values();
        } else {
            // Se non c'è geolocalizzazione, ordina per valutazione media (se disponibile)
            $users = $users->sortByDesc(function ($user) {
                if ($user->type === 'therapist' && $user->therapistProfile) {
                    return $user->therapistProfile->average_rating ?? 0;
                } elseif ($user->type === 'center' && $user->centerProfile) {
                    return $user->centerProfile->average_rating ?? 0;
                }
                return 0;
            })->values();
        }

        return UserResource::collection($users);
    }

    /**
     * Calcola distanza tra due punti (Haversine formula)
     */
    protected function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // in metri

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Verifica stato onboarding
     */
    public function getOnboardingStatus()
    {
        $user = auth()->user();
        
        $isComplete = $this->checkOnboardingComplete($user);
        
        return response()->json([
            'onboarding_completed' => $isComplete,
            'missing_fields' => $isComplete ? [] : $this->getMissingFields($user),
        ]);
    }

    /**
     * Completa onboarding
     */
    public function completeOnboarding(Request $request)
    {
        $user = auth()->user();
        
        // Valida e aggiorna i campi obbligatori
        $this->updateProfile($request);
        
        // Ricarica l'utente con tutte le relazioni per verificare correttamente
        $user->refresh();
        $user->load(['therapistProfile', 'parentPatientProfile', 'centerProfile', 'locations']);
        
        // Verifica completamento
        $isComplete = $this->checkOnboardingComplete($user);
        
        if ($isComplete) {
            $user->update(['onboarding_completed' => true]);
            $user->refresh();
        }
        
        return response()->json([
            'onboarding_completed' => $isComplete,
            'missing_fields' => $isComplete ? [] : $this->getMissingFields($user),
            'user' => new UserResource($user->load(['therapistProfile', 'parentPatientProfile', 'centerProfile', 'locations'])),
        ]);
    }

    /**
     * Verifica se l'onboarding è completo
     */
    protected function checkOnboardingComplete(User $user)
    {
        // Assicurati che le relazioni siano caricate
        if (!$user->relationLoaded('locations')) {
            $user->load('locations');
        }
        if (!$user->relationLoaded('therapistProfile')) {
            $user->load('therapistProfile');
        }
        if (!$user->relationLoaded('parentPatientProfile')) {
            $user->load('parentPatientProfile');
        }
        if (!$user->relationLoaded('centerProfile')) {
            $user->load('centerProfile');
        }

        // Campi base obbligatori per tutti
        if (empty($user->name) || empty($user->surname) || empty($user->email)) {
            return false;
        }

        // Verifica posizione (almeno una location)
        if ($user->locations->isEmpty()) {
            return false;
        }

        // Verifica campi specifici per tipo
        switch ($user->type) {
            case 'parent_patient':
                if (!$user->parentPatientProfile || empty($user->parentPatientProfile->therapies)) {
                    return false;
                }
                break;
            case 'therapist':
                if (!$user->therapistProfile) {
                    return false;
                }
                $profile = $user->therapistProfile;
                if (empty($profile->profession) || empty($profile->therapies) || $profile->hourly_rate === null) {
                    return false;
                }
                break;
            case 'center':
                if (!$user->centerProfile) {
                    return false;
                }
                $profile = $user->centerProfile;
                if (empty($profile->center_name) || empty($profile->partita_iva) || empty($profile->service)) {
                    return false;
                }
                break;
        }

        return true;
    }

    /**
     * Restituisce i campi mancanti per l'onboarding
     */
    protected function getMissingFields(User $user)
    {
        $missing = [];

        if (empty($user->name)) $missing[] = 'name';
        if (empty($user->surname)) $missing[] = 'surname';
        if (empty($user->email)) $missing[] = 'email';
        if ($user->locations()->count() === 0) $missing[] = 'location';

        switch ($user->type) {
            case 'parent_patient':
                if (!$user->parentPatientProfile || empty($user->parentPatientProfile->therapies)) {
                    $missing[] = 'therapies';
                }
                break;
            case 'therapist':
                if (!$user->therapistProfile) {
                    $missing[] = 'therapist_profile';
                } else {
                    $profile = $user->therapistProfile;
                    if (empty($profile->profession)) $missing[] = 'profession';
                    if (empty($profile->therapies)) $missing[] = 'therapies';
                    if ($profile->hourly_rate === null) $missing[] = 'hourly_rate';
                }
                break;
            case 'center':
                if (!$user->centerProfile) {
                    $missing[] = 'center_profile';
                } else {
                    $profile = $user->centerProfile;
                    if (empty($profile->center_name)) $missing[] = 'center_name';
                    if (empty($profile->partita_iva)) $missing[] = 'partita_iva';
                    if (empty($profile->service)) $missing[] = 'service';
                }
                break;
        }

        return $missing;
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Autorizzazione (facoltativo, se necessario)
        // $this->authorize('delete', $user);

        $user->delete();
        return response()->noContent();
    }
}

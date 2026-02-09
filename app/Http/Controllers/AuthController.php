<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;

use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    protected $firebaseAuth;

    public function __construct(FirebaseAuth $firebaseAuth)
    {
        $this->firebaseAuth = $firebaseAuth;
    }

    public function loginOrRegister(Request $request)
    {
        $validatedData = $request->validate([
            'idToken' => 'nullable|string',
            'email' => 'nullable|email',
            'password' => 'nullable|string',
            'name' => 'nullable|string',
            'surname' => 'nullable|string',
        ]);

        if ($request->has('idToken')) {
            try {
                $verifiedIdToken = $this->firebaseAuth->verifyIdToken($validatedData['idToken']);
                $firebaseUserId = $verifiedIdToken->claims()->get('sub');
                $email = $verifiedIdToken->claims()->get('email');

                // Cerca utente per firebase_token o email (con logica corretta)
                $user = User::where(function($query) use ($firebaseUserId, $email) {
                    $query->where('firebase_token', $firebaseUserId)
                          ->orWhere('email', $email);
                })->first();

                if (!$user) {
                    $user = User::create([
                        'firebase_token' => $firebaseUserId,
                        'email' => $email,
                        'name' => $validatedData['name'] ?? null,
                        'surname' => $validatedData['surname'] ?? null,
                        'password' => Hash::make(uniqid()), // Password casuale
                        'type' => $request->type ?? null,
                        'is_premium' => $request->input('is_premium', false),
                        'onboarding_completed' => false,
                    ]);

                    $this->handleUserProfileCreation($user, $validatedData);
                } else {
                    // Aggiorna firebase_token se mancante o diverso
                    if ($user->firebase_token !== $firebaseUserId) {
                        $user->update(['firebase_token' => $firebaseUserId]);
                    }
                    // Aggiorna email se mancante
                    if (empty($user->email) && $email) {
                        $user->update(['email' => $email]);
                    }
                }
            } catch (\Exception $e) {
                return response()->json([
                    'error' => 'Token Firebase non valido',
                    'message' => $e->getMessage()
                ], 401);
            }

        } else if ($request->has(['email', 'password'])) {
            $credentials = $request->only('email', 'password');

            if (Auth::attempt($credentials)) {
                $user = Auth::user();
            } else {
                return response()->json(['error' => 'Credenziali non valide'], 401);
            }

        } else {
            return response()->json(['error' => 'Nessun metodo di autenticazione fornito'], 400);
        }

        if (empty($user->name)) {
            return response()->json([
                'needs_info' => true,
                'message' => 'Nome e cognome richiesti',
            ], 200);
        }

        $token = $user->createToken('authToken')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user),
        ]);
    }

/**
 * Aggiorna nome e cognome per utenti social (protetto da autenticazione)
 * NOTA: Questo metodo è deprecato, usare updateProfile invece
 */
public function updateNameSurnameSocial(Request $request) {
    // Protezione con autenticazione Sanctum
    $user = auth()->user();
    
    if (!$user) {
        return response()->json(['error' => 'Non autenticato'], 401);
    }

    $validatedData = $request->validate([
        'name' => 'required|string|max:255',
        'surname' => 'required|string|max:255',
    ]);

    $user->update([
        'name' => $validatedData['name'],
        'surname' => $validatedData['surname'],
    ]);

    return response()->json([
        'message' => 'Utente aggiornato con successo',
        'user' => new UserResource($user->load(['therapistProfile', 'parentPatientProfile', 'centerProfile']))
    ], 200);
}

    protected function handleUserProfileCreation(User $user, array $data)
    {
        switch ($user->type) {
            case 'therapist':
                $user->therapistProfile()->create([
                    'profession' => $data['profession'] ?? '',
                    'therapies' => $data['therapies'] ?? null,
                    'home_therapy' => $data['home_therapy'] ?? false,
                    'range_home_therapy' => $data['range_home_therapy'] ?? null,
                    'bio' => $data['bio'] ?? null,
                    'hourly_rate' => $data['hourly_rate'] ?? 0,
                    'affiliation_center_id' => $data['affiliation_center_id'] ?? null,
                    'years_of_experience' => $data['years_of_experience'] ?? null,
                ]);
                break;
            case 'parent_patient':
                $user->parentPatientProfile()->create([
                    'relationship' => $data['relationship'] ?? null,
                    'therapies' => $data['therapies'] ?? null,
                ]);
                break;
            case 'center':
                $user->centerProfile()->create([
                    'center_name' => $data['center_name'] ?? '',
                    'partita_iva' => $data['partita_iva'] ?? null,
                    'therapies' => $data['therapies'] ?? null,
                    'service' => $data['service'] ?? null,
                    'description' => $data['description'] ?? null,
                    'logo_url' => $data['logo_url'] ?? null,
                ]);
                break;
        }
    }

    // Metodo per il logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    // Metodo per aggiornare i dati dell'utente
    public function update(Request $request)
    {
        $user = auth()->user();

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'type' => 'required|string|in:therapist,parent_patient,center',
            'is_premium' => 'boolean',
        ]);

        $user->update($validatedData);

        switch ($user->type) {
            case 'therapist':
                $user->therapistProfile()->updateOrCreate(
                    ['user_id' => $user->id],
                    $request->only(['profession', 'therapies', 'home_therapy', 'range_home_therapy', 'bio', 'hourly_rate', 'affiliation_center_id', 'years_of_experience'])
                );
                break;
            case 'parent_patient':
                $user->parentPatientProfile()->updateOrCreate(
                    ['user_id' => $user->id],
                    $request->only(['relationship', 'therapies'])
                );
                break;
            case 'center':
                $user->centerProfile()->updateOrCreate(
                    ['user_id' => $user->id],
                    $request->only(['center_name', 'partita_iva', 'logo_url', 'therapies', 'service', 'description'])
                );
                break;
        }

        return new UserResource($user->load(['therapistProfile', 'parentPatientProfile', 'centerProfile']));
    }
}

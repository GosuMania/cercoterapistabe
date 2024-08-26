<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Kreait\Firebase\Auth as FirebaseAuth;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    protected $firebaseAuth;

    public function __construct(FirebaseAuth $firebaseAuth)
    {
        $this->firebaseAuth = $firebaseAuth;
    }

    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'idToken' => 'required|string',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'type' => 'required|string|in:therapist,parent_patient,center',
            'is_premium' => 'boolean',
        ]);

        // Verifica il token di Firebase
        $verifiedIdToken = $this->firebaseAuth->verifyIdToken($validatedData['idToken']);
        $firebaseUserId = $verifiedIdToken->claims()->get('sub');

        // Crea o trova l'utente nel database locale
        $user = User::updateOrCreate(
            ['firebase_uid' => $firebaseUserId],
            [
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => Hash::make(uniqid()), // Una password casuale generata
                'type' => $validatedData['type'],
                'is_premium' => $request->input('is_premium', false),
            ]
        );

        // Gestione del profilo in base al tipo di utente
        $this->handleUserProfileCreation($user, $validatedData);

        // Genera un token di accesso per l'utente
        $token = $user->createToken('authToken')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user),
        ]);
    }

    protected function handleUserProfileCreation(User $user, array $data)
    {
        switch ($user->type) {
            case 'therapist':
                $user->therapistProfile()->create([
                    'profession' => $data['profession'] ?? '',
                    'specialization' => $data['specialization'] ?? '',
                    'bio' => $data['bio'] ?? '',
                ]);
                break;
            case 'parent_patient':
                $user->parentPatientProfile()->create([
                    'relationship' => $data['relationship'] ?? '',
                    'patient_name' => $data['patient_name'] ?? '',
                    'patient_birthdate' => $data['patient_birthdate'] ?? null,
                ]);
                break;
            case 'center':
                $user->centerProfile()->create([
                    'center_name' => $data['center_name'] ?? '',
                    'service' => $data['service'] ?? '',
                    'description' => $data['description'] ?? '',
                ]);
                break;
        }
    }
    public function login(Request $request)
    {
        $validatedData = $request->validate([
            'idToken' => 'required|string',
        ]);

        // Verifica il token di Firebase
        $verifiedIdToken = $this->firebaseAuth->verifyIdToken($validatedData['idToken']);
        $firebaseUserId = $verifiedIdToken->claims()->get('sub');

        // Cerca l'utente nel database locale
        $user = User::where('firebase_uid', $firebaseUserId)->first();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Genera un nuovo token di accesso
        $token = $user->createToken('authToken')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user),
        ]);
    }

    public function logout(Request $request)
    {
        // Revoca il token di accesso
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}


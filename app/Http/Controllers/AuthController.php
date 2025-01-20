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
        ]);

        if ($request->has('idToken')) {
            $verifiedIdToken = $this->firebaseAuth->verifyIdToken($validatedData['idToken']);
            $firebaseUserId = $verifiedIdToken->claims()->get('sub');
            $email = $verifiedIdToken->claims()->get('email');

            $user = User::where('firebase_token', $firebaseUserId)
                ->orWhere('email', $email)
                ->first();

            if (!$user) {
                $user = User::create([
                    'firebase_token' => $firebaseUserId,
                    'email' => $email,
                    'name' => $verifiedIdToken->claims()->get('given_name') ?? 'Nome',
                    'surname' => $verifiedIdToken->claims()->get('family_name') ?? 'Cognome',
                    'password' => Hash::make(uniqid()), // Password casuale
                    'type' =>$request->type,
                    'is_premium' => $request->input('is_premium', false),
                ]);

                $this->handleUserProfileCreation($user, $validatedData);
            }

        } elseif ($request->has(['email', 'password'])) {
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
            'position' => 'nullable|string|max:255',
        ]);

        $user->update($validatedData);

        switch ($user->type) {
            case 'therapist':
                $user->therapistProfile()->updateOrCreate(
                    ['user_id' => $user->id],
                    $request->only(['profession', 'specialization', 'bio'])
                );
                break;
            case 'parent_patient':
                $user->parentPatientProfile()->updateOrCreate(
                    ['user_id' => $user->id],
                    $request->only(['relationship', 'patient_name', 'patient_birthdate'])
                );
                break;
            case 'center':
                $user->centerProfile()->updateOrCreate(
                    ['user_id' => $user->id],
                    $request->only(['center_name', 'service', 'description'])
                );
                break;
        }

        return new UserResource($user->load(['therapistProfile', 'parentPatientProfile', 'centerProfile']));
    }
}

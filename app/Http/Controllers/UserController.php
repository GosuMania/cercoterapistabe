<?php

namespace App\Http\Controllers;

use App\Http\Resources\SavedUserResource;
use App\Http\Resources\UserResource;
use App\Models\SavedUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
public function index()
{
    $users = User::select('id', 'name', 'surname', 'image_url', 'position', 'address', 'type') // Seleziona i campi specifici
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
            'position' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'type' => 'required|in:therapist,parent_patient,center',
            'is_premium' => 'nullable|boolean',
            'therapies' => 'nullable|array', // JSON
            'profession' => 'nullable|string|max:255', // therapist
            'bio' => 'nullable|string|max:500',       // therapist
            'hourly_rate' => 'nullable|numeric',      // therapist
            'relationship' => 'nullable|string|max:255', // parent_patient
            'center_name' => 'nullable|string|max:255',  // center
            'service' => 'nullable|array',               // JSON
            'description' => 'nullable|string|max:500',  // center
        ]);

        // Aggiorna i dati generici dell'utente
        $user->update([
            'name' => $data['name'],
            'surname' => $data['surname'],
            'image_url' => $data['image_url'] ?? $user->image_url,
            'position' => $data['position'] ?? $user->position,
            'address' => $data['address'] ?? $user->address,
            'type' => $data['type'],
            'is_premium' => $data['is_premium'] ?? $user->is_premium,
        ]);

        // Aggiornamento del profilo specifico
        switch ($user->type) {
            case 'therapist':
                $user->therapistProfile()->updateOrCreate(
                    ['user_id' => $user->id],
                    $request->only(['therapies', 'profession', 'bio', 'hourly_rate'])
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
                    $request->only(['therapies', 'center_name', 'service', 'description'])
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
        ]);

        $query = User::query();

        if (isset($data['type'])) {
            $query->where('type', $data['type']);
        }

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

        if (isset($data['service'])) {
            $query->whereHas('centerProfile', function ($q) use ($data) {
                $q->whereJsonContains('service', $data['service']);
            });
        }

        if (isset($data['is_premium'])) {
            $query->where('is_premium', $data['is_premium']);
        }

        $users = $query->with(['therapistProfile', 'parentPatientProfile', 'centerProfile'])->get();

        return UserResource::collection($users);
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

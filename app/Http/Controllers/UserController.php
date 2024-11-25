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
        ->with(['therapistProfile', 'parentPatientProfile', 'centerProfile']) // Carica le relazioni necessarie
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

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // Autorizzazione (facoltativo, se necessario)
        // $this->authorize('update', $user);

        // Validazione dell'input
        $validatedData = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'type' => 'required|in:therapist,parent_patient,center',
            'is_premium' => 'boolean',
            'position' => 'nullable|string|max:255',
        ])->validate();

        // Aggiornamento dei dati dell'utente
        $user->update($validatedData);

        // Aggiornamento del profilo specifico in base al tipo di utente
        switch ($user->type) {
            case 'therapist':
                $user->therapistProfile()->updateOrCreate(
                    ['user_id' => $user->id],
                    $request->only(['specialization', 'bio', 'profession'])
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

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Autorizzazione (facoltativo, se necessario)
        // $this->authorize('delete', $user);

        $user->delete();
        return response()->noContent();
    }
}

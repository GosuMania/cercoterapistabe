<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['therapistProfile', 'parentPatientProfile', 'centerProfile'])->get();
        return UserResource::collection($users);
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

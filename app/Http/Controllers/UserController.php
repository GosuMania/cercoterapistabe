<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

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

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $user->update($request->only(['name', 'email', 'type', 'is_premium']));

        switch ($user->type) {
            case 'therapist':
                $user->therapistProfile()->updateOrCreate([], $request->only(['specialization', 'bio', 'clinic_address']));
                break;
            case 'parent_patient':
                $user->parentPatientProfile()->updateOrCreate([], $request->only(['relationship', 'patient_name', 'patient_birthdate']));
                break;
            case 'center':
                $user->centerProfile()->updateOrCreate([], $request->only(['center_name', 'address', 'description']));
                break;
        }

        return new UserResource($user->load(['therapistProfile', 'parentPatientProfile', 'centerProfile']));
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return response()->noContent();
    }
}

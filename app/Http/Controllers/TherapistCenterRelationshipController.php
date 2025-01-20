<?php

namespace App\Http\Controllers;

use App\Models\TherapistCenterRelationship;
use Illuminate\Http\Request;

class TherapistCenterRelationshipController extends Controller
{
    public function index(Request $request)
    {
        $relationships = TherapistCenterRelationship::with(['therapist.user', 'center.user'])->get();

        return response()->json($relationships);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'therapist_id' => 'required|exists:therapist_profiles,id',
            'center_id' => 'required|exists:center_profiles,id',
            'status' => 'required|in:Pending,Accepted,Declined',
        ]);

        $relationship = TherapistCenterRelationship::create($data);

        return response()->json($relationship, 201);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'status' => 'required|in:Pending,Accepted,Declined',
        ]);

        $relationship = TherapistCenterRelationship::findOrFail($id);
        $relationship->update($data);

        return response()->json($relationship);
    }
}


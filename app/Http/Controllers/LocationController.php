<?php

namespace App\Http\Controllers;

use App\Http\Resources\LocationResource;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LocationController extends Controller
{
    /**
     * Lista location dell'utente autenticato
     */
    public function index()
    {
        $userId = auth()->id();
        $locations = Location::where('user_id', $userId)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return LocationResource::collection($locations);
    }

    /**
     * Crea una nuova location
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'label' => 'nullable|string|max:255',
            'street_address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country_code' => 'nullable|string|size:2',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'formatted_address' => 'nullable|string',
            'is_default' => 'nullable|boolean',
        ]);

        $userId = auth()->id();

        // Se questa è la location di default, rimuovi il flag dalle altre
        if ($validated['is_default'] ?? false) {
            Location::where('user_id', $userId)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $location = Location::create([
            'user_id' => $userId,
            'label' => $validated['label'] ?? null,
            'street_address' => $validated['street_address'] ?? null,
            'city' => $validated['city'] ?? null,
            'postal_code' => $validated['postal_code'] ?? null,
            'country_code' => $validated['country_code'] ?? null,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'formatted_address' => $validated['formatted_address'] ?? null,
            'is_default' => $validated['is_default'] ?? false,
        ]);

        return new LocationResource($location);
    }

    /**
     * Mostra una location specifica
     */
    public function show($id)
    {
        $location = Location::findOrFail($id);
        $userId = auth()->id();

        if ($location->user_id !== $userId) {
            return response()->json(['error' => 'Non autorizzato'], 403);
        }

        return new LocationResource($location);
    }

    /**
     * Aggiorna una location
     */
    public function update(Request $request, $id)
    {
        $location = Location::findOrFail($id);
        $userId = auth()->id();

        if ($location->user_id !== $userId) {
            return response()->json(['error' => 'Non autorizzato'], 403);
        }

        $validated = $request->validate([
            'label' => 'nullable|string|max:255',
            'street_address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country_code' => 'nullable|string|size:2',
            'latitude' => 'sometimes|numeric|between:-90,90',
            'longitude' => 'sometimes|numeric|between:-180,180',
            'formatted_address' => 'nullable|string',
            'is_default' => 'nullable|boolean',
        ]);

        // Se questa è la location di default, rimuovi il flag dalle altre
        if (isset($validated['is_default']) && $validated['is_default']) {
            Location::where('user_id', $userId)
                ->where('id', '!=', $id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $location->update($validated);

        return new LocationResource($location);
    }

    /**
     * Elimina una location
     */
    public function destroy($id)
    {
        $location = Location::findOrFail($id);
        $userId = auth()->id();

        if ($location->user_id !== $userId) {
            return response()->json(['error' => 'Non autorizzato'], 403);
        }

        $location->delete();

        return response()->noContent();
    }
}

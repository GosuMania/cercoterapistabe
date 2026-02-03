<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'label',
        'street_address',
        'city',
        'postal_code',
        'country_code',
        'latitude',
        'longitude',
        'geom',
        'formatted_address',
        'is_default'
    ];

    /**
     * Casting dei tipi di dato.
     */
    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'is_default' => 'boolean',
    ];

    /**
     * Relazione inversa: la location appartiene a un utente.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Boot del modello per gestire il salvataggio automatico del campo 'geom'
     * se latitudine e longitudine sono presenti.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if ($model->latitude && $model->longitude) {
                // Genera il punto geografico per PostGIS/MySQL Spatial
                // Nota: In SQL l'ordine è POINT(Longitudine Latitudine)
                $model->geom = DB::raw("ST_GeogFromText('POINT({$model->longitude} {$model->latitude})')");
            }
        });
    }

    /**
     * Scope per filtrare le location entro un certo raggio (in metri).
     * ESEMPIO D'USO: Location::withinDistance(45.46, 9.19, 5000)->get();
     */
    public function scopeWithinDistance($query, $lat, $lng, $radiusInMeters)
    {
        return $query->whereRaw(
            "ST_DWithin(geom, ST_GeogFromText('POINT(? ?)'), ?)",
            [$lng, $lat, $radiusInMeters]
        );
    }
}

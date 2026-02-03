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

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'is_default' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function boot()
    {
        parent::boot();

        /**
         * Prima di salvare, popoliamo il campo geom.
         * MySQL richiede un valore per geom perché è NOT NULL.
         */
        static::saving(function ($model) {
            $lat = $model->latitude ?? 0;
            $lng = $model->longitude ?? 0;

            // SRID 4326 è lo standard WGS84 (GPS)
            // L'ordine POINT(lat lng) o POINT(lng lat) può variare in base alla versione di MySQL,
            // ma lo standard geometrico X Y solitamente vede Longitudine (X) e Latitudine (Y).
            $model->geom = DB::raw("ST_GeomFromText('POINT($lat $lng)', 4326)");
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

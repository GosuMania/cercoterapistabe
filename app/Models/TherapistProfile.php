<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TherapistProfile extends Model
{
    protected $fillable = [
        'user_id',
        'profession',
        'therapies',
        'home_therapy',
        'range_home_therapy',
        'bio',
        'hourly_rate',
        'affiliation_center_id',
        'years_of_experience',
    ];

    protected $casts = [
        'therapies' => 'array',
        'home_therapy' => 'boolean',
        'hourly_rate' => 'decimal:2',
        'years_of_experience' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function centers()
    {
        return $this->belongsToMany(CenterProfile::class, 'therapist_center_relationships', 'therapist_id', 'center_id')
            ->withPivot('status')
            ->withTimestamps();
    }

    public function affiliationCenter()
    {
        return $this->belongsTo(CenterProfile::class, 'affiliation_center_id');
    }

    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    /**
     * Calcola la valutazione media
     */
    public function getAverageRatingAttribute()
    {
        $avg = $this->reviews()->approved()->avg('rating');
        return $avg ? round($avg, 2) : 0;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CenterProfile extends Model
{
    protected $fillable = [
        'user_id',
        'center_name',
        'partita_iva',
        'therapies',
        'service',
        'description',
        'logo_url',
    ];

    protected $casts = [
        'therapies' => 'array',
        'service' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function therapists()
    {
        return $this->belongsToMany(TherapistProfile::class, 'therapist_center_relationships', 'center_id', 'therapist_id')
            ->withPivot('status')
            ->withTimestamps();
    }

    public function announcements()
    {
        return $this->hasMany(Announcement::class, 'center_id');
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

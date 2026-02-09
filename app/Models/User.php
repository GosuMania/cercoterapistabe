<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'surname',
        'email',
        'phone',
        'image_url',
        'password',
        'firebase_token',
        'type',
        'is_premium',
        'onboarding_completed',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_premium' => 'boolean',
        'onboarding_completed' => 'boolean',
    ];

    public function therapistProfile()
    {
        return $this->hasOne(TherapistProfile::class);
    }

    public function parentPatientProfile()
    {
        return $this->hasOne(ParentPatientProfile::class);
    }

    public function centerProfile()
    {
        return $this->hasOne(CenterProfile::class);
    }

    public function conversations()
    {
        return $this->belongsToMany(Conversation::class)->withTimestamps();
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function availabilities()
    {
        return $this->hasMany(Availability::class);
    }

    public function locations()
    {
        return $this->hasMany(Location::class);
    }

    public function reviewsGiven()
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    public function interactions()
    {
        return $this->hasMany(UserInteraction::class, 'viewer_id');
    }

    public function interactionsReceived()
    {
        return $this->hasMany(UserInteraction::class, 'viewed_id');
    }
}

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
        'image_url',
        'password',
        'firebase_token',
        'position',
        'address',
        'type',
        'is_premium',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_premium' => 'boolean',
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
}

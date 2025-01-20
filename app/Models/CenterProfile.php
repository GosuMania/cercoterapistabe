<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CenterProfile extends Model
{
    protected $fillable = ['user_id', 'center_name', 'therapies', 'service', 'description'];

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
}

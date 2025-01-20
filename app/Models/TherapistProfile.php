<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TherapistProfile extends Model
{
    protected $fillable = ['user_id', 'profession', 'therapies', 'bio', 'hourly_rate'];

    protected $casts = [
        'therapies' => 'array',
        'hourly_rate' => 'decimal:2',
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
}

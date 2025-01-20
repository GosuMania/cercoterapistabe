<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TherapistCenterRelationship extends Model
{
    protected $fillable = ['therapist_id', 'center_id', 'status'];

    protected $casts = [
        'status' => 'string',
    ];

    public function therapist()
    {
        return $this->belongsTo(TherapistProfile::class, 'therapist_id');
    }

    public function center()
    {
        return $this->belongsTo(CenterProfile::class, 'center_id');
    }
}

